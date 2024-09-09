<?php

namespace Leantime\Domain\Wiki\Controllers {

    use Leantime\Core\Controller\Controller;
    use Leantime\Core\Controller\Frontcontroller;
    use Leantime\Domain\Auth\Models\Roles;
    use Leantime\Domain\Auth\Services\Auth;
    use Leantime\Domain\Wiki\Repositories\Wiki as WikiRepository;

    /**
     *
     */
    class DelWiki extends Controller
    {
        private WikiRepository $wikiRepo;

        /**
         * init - init
         *
         * @access public
         */
        public function init(WikiRepository $wikiRepo)
        {
            $this->wikiRepo = $wikiRepo;
        }

        /**
         * run - display template and edit data
         *
         * @access public
         */
        public function run()
        {

            Auth::authOrRedirect([Roles::$owner, Roles::$admin, Roles::$manager, Roles::$editor]);

            if (isset($_GET['id'])) {
                $id = (int)($_GET['id']);
            }

            if (isset($_POST['del']) && isset($id)) {
                $this->wikiRepo->delWiki($id);

                session()->forget("currentIdeaCanvas");
                $this->tpl->setNotification($this->language->__("notification.wiki_deleted"), "success", "wiki_deleted");

                $this->tpl->closeModal();
                $this->tpl->htmxRefresh();

                return $this->tpl->emptyResponse();
            }

            return $this->tpl->displayPartial('wiki::partials.delWiki');
        }
    }
}
