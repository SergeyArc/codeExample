<?php


namespace services\grid;

use InvalidArgumentException;

trait GridTrait
{
    protected function countQueryData($query)
    {
        $countQuery = clone $query;
        $countQuery->select('COUNT(DISTINCT id) AS count');

        $count = $countQuery->fetchOne();

        return (int) $count['count'];
    }

    protected function addMandatoryParams($query, $parameters)
    {
        $searchData = [
            'groupOp' => 'AND',
            'rules' => array()
        ];

        foreach ($parameters as $parameter) {
            $searchData['rules'][] = [
                'field' => $parameter["field"],
                'op' => $parameter["op"],
                'data' => $parameter["data"]
            ];
        }

        $whereParts = $query->getDqlPart('where');
        $statusExist = false;

        if (!empty($whereParts)) {
            foreach ($whereParts as $wherePart) {
                if ((bool) preg_match('/status/', $wherePart) === true) {
                    $statusExist = true;
                }
            }
        }

        if ($statusExist === false) {
            $searchData['rules'][] = [
                'field' => "status",
                'op' => 'ne',
                'data' => 'deleted'
            ];
        }

        $this->getWhereClause($query, $searchData);
    }

    protected function getWhereClause($query, $searchData)
    {
        if (empty($searchData)) {
            return;
        }

        foreach ($searchData['rules'] as $i => $rule) {
            if ($rule['field'] == "experts") {
                $searchData['rules'][$i]['field'] = "exp.id";
            }

            if ($rule['field'] == "tags") {
                $searchData['rules'][$i]['field'] = "t.id";
            }

            if (in_array($rule['field'], ["date", "start_date", "end_date"])) {
                if ((validateDate($rule['data'], 'd.m.Y')) === false) {
                    throw new InvalidArgumentException('jsGrid: некорректный формат даты');
                }

                $searchData['rules'][$i]['field'] = "DATE(".$rule['field'].")";
                $searchData['rules'][$i]['data'] = tosqldate($rule['data']);
            }

            if ($rule["op"] == "in") {
                if (is_array($rule['data']) === false) {
                    throw new InvalidArgumentException('jsGrid: некорректный параметр фильта (должен быть массив)');
                } else {
                    foreach ($rule['data'] as $value) {
                        if (is_int($value) === false) {
                            throw new InvalidArgumentException('jsGrid: некорректный параметр фильта (должно быть число)');
                        }
                    }
                }
            }
        }

        if (count($searchData['rules']) > 10) {
            throw new InvalidArgumentException('jsGrid: превышено допустимое число фильтров');
        }

        $sql = "";
        $sqlData = [];
        $firstElem = true;
        foreach ($searchData['rules'] as $rule) {
            if (!$firstElem) {
                switch ($searchData['groupOp']) {
                    case 'AND':
                        $sql .= " AND ";
                        break;
                    case 'OR':
                        $sql .= " OR ";
                        break;
                    default:
                        $sql .= " AND ";
                }
            } else {
                $firstElem = false;
            }

            switch ($rule['op']) {
                case 'eq':
                    $sql .= $rule['field']." = ?";
                    $sqlData[] = $rule['data'];
                    break;
                case 'ne':
                    $sql .= $rule['field']." <> ?";
                    $sqlData[] = $rule['data'];
                    break;
                case 'bw':
                    $sql .= $rule['field']." LIKE ?";
                    $sqlData[] = $rule['data'].'%';
                    break;
                case 'ew':
                    $sql .= $rule['field']." LIKE ?";
                    $sqlData[] = '%'.$rule['data'];
                    break;
                case 'cn':
                    $sql .= $rule['field']." LIKE ?";
                    $sqlData[] = '%'.$rule['data'].'%';
                    break;
                case 'lt':
                    $sql .= $rule['field']." < ?";
                    $sqlData[] = $rule['data'];
                    break;
                case 'gt':
                    $sql .= $rule['field']." > ?";
                    $sqlData[] = $rule['data'];
                    break;
                case 'ge':
                    $sql .= $rule['field']." >= ?";
                    $sqlData[] = $rule['data'];
                    break;
                case 'le':
                    $sql .= $rule['field']." <= ?";
                    $sqlData[] = $rule['data'];
                    break;
                case 'in':
                    $sql .= $rule['field']." IN (".implode(",",$rule['data']).")";
                    break;
                default:
                    $sql .= $rule['field']." = ?";
                    $sqlData[] = $rule['data'];
            }
        }

        if (empty($sql)) {
            return;
        }

        $where = (empty($query->getDqlPart('where'))) ? 'where' : 'andWhere';

        $query->$where($sql, $sqlData);

        return $query;
    }
}