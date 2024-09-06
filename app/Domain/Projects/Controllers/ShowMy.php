<?php

namespace Leantime\Domain\Projects\Controllers {

    use Leantime\Core\Controller\Controller;
    use Leantime\Domain\Clients\Repositories\Clients as ClientRepository;
    use Leantime\Domain\Menu\Services\Menu;
    use Leantime\Domain\Projects\Services\Projects as ProjectService;

    /**
     *
     */
    class ShowMy extends Controller
    {
        private ProjectService $projectService;
        private ClientRepository $clientRepo;

        private Menu $menuService;

        /**
         * @param ProjectService   $projectService
         * @param TicketService    $ticketService
         * @param ReportService    $reportService
         * @param CommentService   $commentService
         * @param ClientRepository $clientRepo
         * @return void
         */
        public function init(
            ProjectService $projectService,
            ClientRepository $clientRepo,
            Menu $menuService
        ): void {
            $this->projectService = $projectService;
            $this->clientRepo = $clientRepo;
            $this->menuService = $menuService;
        }

        /**
         * run - display template and edit data
         *
         * @access public
         */
        public function get()
        {

            $clientId = "";
            $currentClientName = "";

            if (isset($_GET['client']) === true && $_GET['client'] != '') {
                $clientId = (int)$_GET['client'];
                $currentClient = $this->clientRepo->getClient($clientId);
                if (is_array($currentClient) && count($currentClient) > 0) {
                    $currentClientName = $currentClient['name'];
                }
            }

            $allprojects = $this->projectService->getProjectsAssignedToUser(session("userdata.id"), 'open');
            $clients = array();

            $projectResults = array();
            $i = 0;

            if (is_array($allprojects)) {
                foreach ($allprojects as $project) {

                    if (!array_key_exists($project["clientId"], $clients)) {
                        $clients[$project["clientId"]] = array("name" => $project['clientName'], "id" => $project["clientId"]);
                    }

                    if ($clientId == "" || $project["clientId"] == $clientId) {
                        $projectResults[$i] = $project;
                        $i++;
                    }
                }
            }

            $projectTypeAvatars = $this->menuService->getProjectTypeAvatars();

            $this->tpl->assign("projectTypeAvatars", $projectTypeAvatars);
            $this->tpl->assign("currentClientName", $currentClientName);
            $this->tpl->assign("currentClient", $clientId);
            $this->tpl->assign("clients", $clients);
            $this->tpl->assign("allProjects", $projectResults);
            return $this->tpl->display('projects.projectHub');
        }
    }
}
