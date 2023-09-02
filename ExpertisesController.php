<?php
use services\acl\ACL;
use services\grid\ExpertisesGrid as Grid;
use services\tags\Tags as TagsServive;
use services\cache\Cache as CacheServive;
use services\export\ExportCSV as Export;
use services\settings\Settings as ExpertSettings;


class ExpertisesController extends Zend_Controller_Action {
    public function init() {
        $this->_flashMessenger = $this->_helper->getHelper ( 'FlashMessenger' );
        $this->view->addScriptPath(APPLICATION_PATH . '/helpers/partials');

        Zend_Layout::startMvc ( array (
            'layoutPath' => APPLICATION_PATH . '/layouts',
            'layout' => 'main'
        ));

        if (! Auth::isAuthorized ()) {
            $this->_redirect('/auth');
        }

        $this->view->user = $this->user = Zend_Auth::getInstance()->getIdentity();
    }

    public function indexAction() {
        $ExpertSettings = new ExpertSettings(new Settings(), new CacheServive());
        $this->view->searchTemplates = $ExpertSettings->getExpertSettings($this->user->id, 'grid_expertises_query');
    }

    public function gridAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        try {
            $grid = new Grid(new Expertises(), new CacheServive(), array(
                    [
                        'field' => "department",
                        'op' => 'eq',
                        'data' => $this->user->department
                    ]
                )
            );
            $grid->setPage((int) $_POST['page']);
            $grid->setRowsPerPage((int) $_POST['rows']);
            $grid->setSortingField($_POST['sidx']);
            $grid->setSortingOrder($_POST['sord']);
            $grid->setFirstRowIndex();
            $grid->setSearchData( Zend_Json::decode($_POST['filters']) );

            $this->_helper->json( $grid->getGridData() );
        } catch ( Exception $e ) {
            Logs::log('JsGrid ERROR: '.$e->getMessage());
            $this->_helper->json([
                "page" => 1,
                "total" => 0,
                "records" => 0
            ]);
        }
    }

    public function exportToCsvAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        try {
            $grid = new Grid(new Expertises(), new CacheServive(), array(
                    [
                        'field' => "department",
                        'op' => 'eq',
                        'data' => $this->user->department
                    ]
                )
            );

            $grid->setPage((int) $_POST['page']);
            $grid->setRowsPerPage((int) $_POST['rows']);
            $grid->setSortingField($_POST['sidx']);
            $grid->setSortingOrder($_POST['sord']);
            $grid->setFirstRowIndex();
            $grid->setSearchData( Zend_Json::decode($_POST['filters']) );

            $header = [
                'Зарегистрирована',
                'Номер',
                'Учреждение',
                'Отдел',
                'Инициатор',
                'Эксперт',
                'Статус',
                'Вид',
                'Категория',
                'Начало выполнения',
                'Окончание выполнения',
                'Выводы',
                'Категория сложности',
                'Признаки',
                'Классификация'
            ];

            $export = new Export("expertises.csv", $grid->getCSVData(), $header);
            $export->export();

            $this->_helper->json([
                'response' => 'success'
            ]);
        } catch ( Exception $e ) {
            Logs::log('JsGrid ERROR: '.htmlspecialchars($e));
            $this->_helper->json([
                'response' => 'error'
            ]);
        }
    }
}
