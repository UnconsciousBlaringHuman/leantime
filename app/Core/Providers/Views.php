<?php

namespace Leantime\Core\Providers;

use Illuminate\Container\Container;
use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\DynamicComponent;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\ViewServiceProvider;
use Leantime\Core\Support\PathManifestRepository;
use Leantime\Core\UI\Composer;

class Views extends ViewServiceProvider
{

    protected $viewPaths;

    protected $pathRepo;



    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        $this->app['config']->set('view.compiled', $this->app->basePath() . "/cache/views/");
        $this->app['config']->set('view.cache', true);
        $this->app['config']->set('view.compiled_extension', 'php');

        $this->registerFactory();
        $this->registerViewFinder();
        $this->registerBladeCompiler();
        $this->registerEngineResolver();

        $this->app->terminating(static function () {
            \Illuminate\View\Component::flushCache();
        });
    }

    /**
     * Register the view environment.
     *
     * @return void
     */
    public function registerFactory()
    {
        $this->app->singleton('view', function ($app) {
            // Next we need to grab the engine resolver instance that will be used by the
            // environment. The resolver will be used by an environment to get each of
            // the various engine implementations such as plain PHP or Blade engine.
            $resolver = $app['view.engine.resolver'];

            $finder = $app['view.finder'];

            $factory = $this->createFactory($resolver, $finder, $app['events']);

            array_map(fn($ext) => $factory->addExtension($ext, 'blade'), ['inc.php', 'sub.php', 'tpl.php']);

            // reprioritize blade
            $factory->addExtension('blade.php', 'blade');

            // We will also set the container instance on this view environment since the
            // view composers may be classes registered in the container, which allows
            // for great testable, flexible composers for the application developer.
            $factory->setContainer(app());

            $factory->share('app', $app);

            //Find and set composers
            $composers = $this->getComposerPaths();
            foreach ($composers as $composerClass) {
                if (
                    is_subclass_of($composerClass, Composer::class) &&
                    !(new \ReflectionClass($composerClass))->isAbstract()
                ) {
                    $factory->composer($composerClass::$views, $composerClass);
                }
            }

            $app->terminating(static function () {
                \Illuminate\View\Component::forgetFactory();
            });

            return $factory;
        });
    }

    /**
     * Create a new Factory Instance.
     *
     * @param \Illuminate\View\Engines\EngineResolver $resolver
     * @param \Illuminate\View\ViewFinderInterface $finder
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     * @return \Illuminate\View\Factory
     */
    protected function createFactory($resolver, $finder, $events)
    {
        return new Factory($resolver, $finder, $events);
    }

    /**
     * Register the view finder implementation.
     *
     * @return void
     */
    public function registerViewFinder()
    {
        $this->app->bind('view.finder', function ($app) {

            $fileViewFinder = new FileViewFinder($app['files'], []);

            $this->viewPaths = $this->getViewPaths();

            array_map([$fileViewFinder, 'addNamespace'], array_keys($this->viewPaths), array_values($this->viewPaths));

            return $fileViewFinder;
        });
    }

    /**
     * Register the Blade compiler implementation.
     *
     * @return void
     */
    public function registerBladeCompiler()
    {
        $this->app->singleton('blade.compiler', function ($app) {
            $compiler = new BladeCompiler(
                $app['files'],
                $app->basePath() . "/cache/views",
                $app->basePath(),
                $app['config']->get('view.cache', true),
                $app['config']->get('view.compiled_extension', 'php'),
            );

            if(!$this->viewPaths){
                $this->viewPaths = $this->getViewPaths();
            }

            $namespaces = array_keys($this->viewPaths);
            array_map(
                [$compiler, 'anonymousComponentNamespace'],
                array_map(fn($namespace) => "$namespace::components", $namespaces),
                $namespaces
            );

            return tap($compiler, function ($blade) {
                $blade->component('dynamic-component', DynamicComponent::class);
            });
        });
    }

    /**
     * Register the engine resolver instance.
     *
     * @return void
     */
    public function registerEngineResolver()
    {
        $this->app->singleton('view.engine.resolver', function () {
            $resolver = new EngineResolver;

            // Next, we will register the various view engines with the resolver so that the
            // environment will resolve the engines needed for various views based on the
            // extension of view file. We call a method for each of the view's engines.
            foreach (['file', 'php', 'blade'] as $engine) {
                $this->{'register' . ucfirst($engine) . 'Engine'}($resolver);
            }

            return $resolver;
        });
    }

    /**
     * Register the file engine implementation.
     *
     * @param \Illuminate\View\Engines\EngineResolver $resolver
     * @return void
     */
    public function registerFileEngine($resolver)
    {
        $resolver->register('file', function () {
            return new FileEngine(Container::getInstance()->make('files'));
        });
    }

    /**
     * Register the PHP engine implementation.
     *
     * @param \Illuminate\View\Engines\EngineResolver $resolver
     * @return void
     */
    public function registerPhpEngine($resolver)
    {
        $resolver->register('php', function () {
            return new PhpEngine(Container::getInstance()->make('files'));
        });
    }

    /**
     * Register the Blade engine implementation.
     *
     * @param \Illuminate\View\Engines\EngineResolver $resolver
     * @return void
     */
    public function registerBladeEngine($resolver)
    {
        $resolver->register('blade', function () {
            $app = Container::getInstance();

            $compiler = new CompilerEngine(
                $app->make('blade.compiler'),
                $app->make('files'),
            );

            $app->terminating(static function () use ($compiler) {
                $compiler->forgetCompiledOrNotExpired();
            });

            return $compiler;
        });
    }

    public function boot()
    {
    }

    public function getComposerPaths()
    {
        $pathRepo = app()->make(PathManifestRepository::class);

        if ($viewPaths = $pathRepo->loadManifest("composerPaths")) {
            return $viewPaths;
        }

        $storePaths = $this->discoverComposerPaths();

        $viewPaths = $pathRepo->writeManifest("composerPaths", $storePaths);

        return $viewPaths;
    }

    private function discoverComposerPaths()
    {
        $customComposerClasses = collect(glob(APP_ROOT . '/custom/Views/Composers/*.php'))
            ->concat(glob(APP_ROOT . '/custom/Domain/*/Composers/*.php'));

        $appComposerClasses = collect(glob(APP_ROOT . '/app/Views/Composers/*.php'))
            ->concat(glob(APP_ROOT . '/app/Domain/*/Composers/*.php'));

        $pluginComposerClasses = collect(
            $this->app->make(\Leantime\Domain\Plugins\Services\Plugins::class)->getEnabledPlugins()
        )
            ->map(fn($plugin) => glob(APP_ROOT . '/app/Plugins/' . $plugin->foldername . '/Composers/*.php'))
            ->flatten();

        $testers = $customComposerClasses->map(fn($path) => str_replace('/custom/', '/app/', $path));

        $stockComposerClasses = $appComposerClasses
            ->concat($pluginComposerClasses)
            ->filter(fn($composerClass) => !$testers->contains($composerClass));

        $storeComposers = $customComposerClasses
            ->concat($stockComposerClasses)
            ->map(fn($filepath) => Str::of($filepath)
                ->replace([APP_ROOT . '/app/', APP_ROOT . '/custom/', '.php'], ['', '', ''])
                ->replace('/', '\\')
                ->start($this->app->getNamespace())
                ->toString())
            ->all();

        return $storeComposers;
    }

    public function getViewPaths()
    {
        $pathRepo = app()->make(PathManifestRepository::class);

        if ($viewPaths = $pathRepo->loadManifest("viewPaths")) {
            return $viewPaths;
        }

        $storePaths = $this->discoverViewPaths();

        $viewPaths = $pathRepo->writeManifest("viewPaths", $storePaths);

        return $viewPaths;
    }

    private function discoverViewPaths()
    {
        $domainPaths = collect(glob($this->app->basePath() . '/app/Domain/*'))
            ->mapWithKeys(fn($path) => [
                $basename = strtolower(basename($path)) => [
                    APP_ROOT . '/custom/Domain/' . $basename . '/Templates',
                    "$path/Templates",
                ],
            ]);

        $plugins = collect($this->app->make(\Leantime\Domain\Plugins\Services\Plugins::class)->getEnabledPlugins());

        $pluginPaths = $plugins->mapWithKeys(function ($plugin) use ($domainPaths) {
            //Catch issue when plugins are cached on load but autoloader is not quite done loading.
            //Only happens because the plugin objects are stored in session and the unserialize is not keeping up.
            //Clearing session cache in that case.
            //@TODO: Check on callstack to make sure autoload loads before sessions
            if (!is_a($plugin, '__PHP_Incomplete_Class')) {
                if ($domainPaths->has($basename = strtolower($plugin->foldername))) {
                    //Clear cache, something is up
                    //session()->forget("enabledPlugins");
                    return [];
                }

                if ($plugin->format == "phar") {
                    $path = 'phar://' . APP_ROOT . '/app/Plugins/' . $plugin->foldername . '/' . $plugin->foldername . '.phar/Templates';
                } else {
                    $path = APP_ROOT . '/app/Plugins/' . $plugin->foldername . '/Templates';
                }

                return [$basename => [$path]];
            }

            //session()->forget("enabledPlugins");
            return [];
        });

        $storePaths = $domainPaths
            ->merge($pluginPaths)
            ->merge(['global' => APP_ROOT . '/app/Views/Templates'])
            ->merge(['__components' => $this->app['config']->get('view.compiled')])
            ->all();

        return $storePaths;
    }
}