<?php


namespace services\grid;


class ExpertisesGrid extends Grid
{
    public function setColumns()
    {
        $this->columns = array(
            "date",
            "type",
            "number",
            "out_number",
            "status"
        );
    }

    public function getGridData()
    {
        $queryData = [
            "source" => "ExpertisesGrid:getGridData",
            "target" => "Grid",
            "mandatoryParams" => $this->mandatoryParams,
            "rowsPerPage" => $this->rowsPerPage,
            "firstRowIndex" => $this->firstRowIndex,
            "sortingField" => $this->sortingField,
            "sortingOrder" => $this->sortingOrder,
            "searchData" => $this->searchData
        ];

        $cacheName = md5(serialize($queryData));
        $data = $this->cacheService->getData($cacheName);

        if ($data === false) {
            $data = $this->prepareResponse( $this->queryData() );
            $this->cacheService->saveData($data, $cacheName, "jqGridExpertisesList");
        }

        return $data;
    }

    public function getCSVData()
    {
        $queryData = [
            "source" => "Expertises",
            "target" => "CSV",
            "department" => $this->department,
            "rowsPerPage" => $this->rowsPerPage,
            "firstRowIndex" => $this->firstRowIndex,
            "sortingField" => $this->sortingField,
            "sortingOrder" => $this->sortingOrder,
            "searchData" => $this->searchData
        ];

        $cacheName = md5(serialize($queryData));
        $data = $this->cacheService->getData($cacheName);

        if ($data === false) {
            $data = $this->prepareCSVResponse( $this->queryData() );
            $this->cacheService->saveData($data, $cacheName, "jqCSVExpertisesList");
        }

        return $data;
    }

    private function prepareResponse($gridData)
    {
        $countData = $gridData["quantity"];
        $response['page'] = $this->page;
        $response['total'] = ceil($countData / $this->rowsPerPage);
        $response['records'] = $countData;

        foreach ($gridData["data"] as $key => $result) {
            $response['rows'][$key]['id'] = $result['id'];
            $response['rows'][$key]['date'] = $result['date'];
            $response['rows'][$key]['start_date'] = $result['start_date'];
            $response['rows'][$key]['end_date'] = $result['end_date'];
            $response['rows'][$key]['number'] = $result['number'];
            $response['rows'][$key]['out_number'] = $result['out_number'];
            $response['rows'][$key]['status'] = $this->formatStatus($result['status']);
            $response['rows'][$key]['category'] = $result ['Categories'] ['shortname'];
            $response['rows'][$key]['experts'] = $this->formatExperts($result['Experts']);
            $response['rows'][$key]['complex'] = $result ['complex'];
            $response['rows'][$key]['commission'] = $result ['commission'];
            $response['rows'][$key]['initiator_institution'] = (isset ( $result ['Institutions'] ['name'] ) && $result ['Institutions'] ['name'] != 'Не задано') ? $result ['Institutions'] ['name'] : '';
            $response['rows'][$key]['initiator_department'] = (isset ( $result ['Departments'] ['name'] ) && $result ['Departments'] ['name'] != 'Не задано') ? $result ['Departments'] ['name'] : '';
            $response['rows'][$key]['initiator'] = (isset ( $result ['Initiators'] ['name'] )) ? $result ['Initiators'] ['name'] : '';
        }

        return $response;
    }

    private function prepareCSVResponse($gridData)
    {
        $response = [];

        foreach ($gridData["data"] as $key => $result)
        {
            $response[$key]['date'] = ( !empty($result['date']) ) ? mydate($result['date']) : '';
            $response[$key]['number'] = $result['number'];
            $response[$key]['out_number'] = $result['out_number'];
            $response[$key]['initiator_institution'] = (isset($result['Institutions']['name'])) ? $result['Institutions']['name'] : '';
            $response[$key]['initiator_department'] = (isset($result['Departments']['name'])) ? $result['Departments']['name'] : '';
            $response[$key]['initiator'] = (isset($result['Initiators']['name'])) ? $result['Initiators']['name'] : '';
            $response[$key]['experts'] = $this->formatExperts($result['Experts']);
            $response[$key]['status'] = $this->formatStatus($result['status']);
            $response[$key]['category'] = $result ['Categories'] ['shortname'];
            $response[$key]['start_date'] = (!empty($result['start_date'])) ? mydate($result['start_date']) : '';
            $response[$key]['end_date'] = (!empty($result ['end_date'])) ? mydate($result['end_date']) : '';
            $response[$key]['findings'] = $result['findings'];
            $response[$key]['ComplexitySigns'] = $this->formatComplexity($result['complexity']);

            if ($result ['status'] == "finished") {
                $response[$key]['findings_class'] = $this->formatFindings($result['findings_class']);
            } else {
                $response[$key]['findings_class'] = "";
            }
        }

        return $response;
    }
}