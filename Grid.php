<?php


namespace services\grid;


use InvalidArgumentException;

abstract class Grid
{
    protected $model;
    protected $mandatoryParams;
    protected $cacheService;
    protected $columns;  //  array of allowed columns names
    protected $sortingField;
    protected $sortingOrder;
    protected $page;
    protected $rowsPerPage;
    protected $firstRowIndex;
    protected $searchData;

    public function __construct($modelObject, $cacheService, $mandatoryParams = [])
    {
        $this->model = $modelObject;
        $this->cacheService = $cacheService;
        $this->setMandatoryParams($mandatoryParams);
        $this->setColumns();
        $this->setSortingField();
        $this->setPage();
        $this->setRowsPerPage();
        $this->setSortingOrder();
        $this->setFirstRowIndex();
    }

    abstract public function setColumns();
    abstract public function getGridData();

    public function setPage($page = 1)
    {
        $page = (int) $page;
        if ($page <= 0) {
            $page = 1;
        }
        $this->page = $page;
    }

    public function setRowsPerPage($rows = 20)
    {
        $rows = (int) $rows;
        if ($rows <= 0 || !in_array($rows, [20, 40, 60])) {
            $rows = 20;
        }
        $this->rowsPerPage = $rows;
    }

    public function setSortingField($field = false)
    {
        if ($field === false || !in_array($field, $this->columns)) {
            $this->sortingField = $this->columns[0];
        } else {
            $this->sortingField = $field;
        }
    }

    public function setSortingOrder($order = 'ASC')
    {
        if ( !in_array(strtoupper($order), ['ASC', 'DESC']) ) {
            $this->sortingOrder = 'ASC';
        } else {
            $this->sortingOrder = strtoupper($order);
        }
    }

    public function setFirstRowIndex()
    {
        $this->firstRowIndex = $this->page * $this->rowsPerPage - $this->rowsPerPage;
    }

    public function setSearchData($searchData)
    {
        $this->searchData = (empty($searchData)) ? [] : $searchData;
    }

    public function setMandatoryParams($params)
    {
        if (!is_array($params)) {
            $params = [];
        } elseif (!empty($params)) {
            foreach ($params as $param) {
                if ( !isset($param['field'], $param['op'], $param['data']) ) {
                    throw new InvalidArgumentException('jsGrid: mandatory params must be an array');
                }
            }
        }

        $this->mandatoryParams = $params;
    }

    protected function formatExperts($expertArray)
    {
        $experts = "";
        $first = true;
        foreach ($expertArray as $expert) {
            if ($first) {
                $first = false;
            } else {
                $experts .= ", ";
            }
            $experts .= $expert['name'] . ' ';
        }

        return $experts;
    }

    protected function formatStatus($status)
    {
        switch ($status)
        {
            case "queue":
                return "в очереди";
            case "underway":
                return "в работе";
            case "finished":
                return "завершена";
            case "suspended":
                return "приостановлена";
            case "returned":
                return "отозвана";
            default:
                return "";
        }
    }

    protected function formatComplexity($complexity)
    {
        switch ($complexity)
        {
            case "middle":
                return "Средняя";
            case "difficult":
                return "Сложная";
            case "heavy":
                return "Особо сложная";
            default:
                return "Простая";
        }
    }

    protected function formatFindings($findings)
    {
        switch ($findings)
        {
            case "categorical":
                return "Категорический";
            case "probable":
                return "Вероятный";
            case "impossible":
                return "НПВ";
            default:
                return "";
        }
    }

    protected function queryData()
    {
        return $this->model->getGridData(
            $this->rowsPerPage,
            $this->firstRowIndex,
            $this->sortingField,
            $this->sortingOrder,
            $this->searchData,
            $this->mandatoryParams
        );
    }

}