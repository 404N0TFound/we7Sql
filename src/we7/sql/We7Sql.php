<?php

namespace We7\Sql;

class We7Sql
{
    protected $tablename;

    /**
     * @param array $get
     * @param array $conditon
     * @param string $orderBy
     * @param string $limit
     * @return array|bool
     */
    private function buildSql($get = [], $conditon = [], $orderBy = '', $limit = '')
    {
        if (empty($this->tablename)) {
            return false;
        }
        if (!empty($get)) {
            $fields = implode(',', $get);
        } else {
            $fields = '*';
        }
        $wheresRet = $this->buildWheres($conditon);
        $sql = "SELECT {$fields} FROM " . tablename($this->tablename) . "
            WHERE 1 {$wheresRet['wheres']} ";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        } else {
            $sql .= " ORDER BY id DESC";
        }
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        return ['sql' => $sql, 'cond' => $wheresRet['cond']];
    }

    /**
     * @param $conditon
     * @return array
     */
    public function buildWheres($conditon)
    {
        $cond = [];
        $wheres = '';
        if (!empty($conditon)) {
            foreach ($conditon as $k => $v) {
                if (is_array($v) && !empty($v)) {
                    $vStr = implode(',', $v);
                    $wheres .= " AND `{$k}` IN ({$vStr})";
                } else if (strstr($k, 'like')) {
                    $k = explode('-', $k)[1];
                    $wheres .= " AND `{$k}` LIKE :{$k}";
                    $cond[":{$k}"] = "%{$v}%";
                } else if (strstr($k, 'gt')) {
                    $k = explode('-', $k)[1];
                    $wheres .= " AND `{$k}` > {$v}";
                } else if (strstr($k, 'lt')) {
                    $k = explode('-', $k)[1];
                    $wheres .= " AND `{$k}` < {$v}";
                } else {
                    $wheres .= " AND `{$k}` = :{$k}";
                    $cond[":{$k}"] = $v;
                }
            }
        }
        return ['wheres' => $wheres, 'cond' => $cond];
    }

    /**
     * @param array $get
     * @param array $conditon
     * @param string $orderBy
     * @param string $limit
     * @param bool $isBatch
     * @return array|bool
     */
    public function getList($get = [], $conditon = [], $orderBy = '', $limit = '')
    {
        global $_W;
        if (!empty($get)) {
            // 返回的键
            $returnKey = $get[0];
        } else {
            $returnKey = 'id';
        }
        if (!empty($_W['uniacid']) && pdo_fieldexists($this->tablename, 'uniacid')) {
            $conditon['uniacid'] = $_W['uniacid'];
        }
        $sqlResult = $this->buildSql($get, $conditon, $orderBy, $limit);
        if ($sqlResult == false) {
            return [];
        }
        $ret = [];
        $list = pdo_fetchall($sqlResult['sql'], $sqlResult['cond']);
        if (!empty($list)) {
            foreach ($list as $row) {
                if (!empty($row['ext_info'])) {
                    $row['ext_info'] = json_decode($row['ext_info'], 1);
                    if (!empty($row['ext_info']['logo'])) {
                        $row['ext_info']['logo'] = tomedia($row['ext_info']['logo']);
                    }
                    if (!empty($row['ext_info']['pic'])) {
                        $row['ext_info']['pic'] = tomedia($row['ext_info']['pic']);
                    }
                }
                if (isset($row[$returnKey])) {
                    $ret[$row[$returnKey]] = $row;
                } else {
                    $ret[] = $row;
                }
            }
        }
        return $ret;
    }

    /**
     * @param array $get
     * @param array $conditon
     * @param string $orderBy
     * @return array|bool
     */
    public function getOne($get = [], $conditon = [], $orderBy = '')
    {
        $list = $this->getList($get, $conditon, $orderBy, 1);
        $list = array_values($list);
        return $list[0];
    }

    /**
     * @param string $get
     * @param array $conditon
     * @param string $orderBy
     * @param string $limit
     * @return array|bool
     */
    public function getValue($get = "count(*)", $conditon = [], $orderBy = '', $limit = '')
    {
        global $_W;
        if (!empty($_W['uniacid']) && pdo_fieldexists($this->tablename, 'uniacid')) {
            $conditon['uniacid'] = $_W['uniacid'];
        }
        $sqlResult = $this->buildSql([$get], $conditon, $orderBy, $limit);
        if ($sqlResult == false) {
            return [];
        }
        $ret = pdo_fetchcolumn($sqlResult['sql'], $sqlResult['cond']);
        return $ret;
    }

    /**
     * @param $insert
     * @return bool
     */
    public function save($insert)
    {
        global $_W;
        if (empty($insert)) {
            return false;
        }
        if (!empty($_W['uniacid']) && pdo_fieldexists($this->tablename, 'uniacid')) {
            $insert['uniacid'] = $_W['uniacid'];
        }
        $ret = pdo_insert($this->tablename, $insert);
        if ($ret) {
            return pdo_insertid();
        } else {
            return false;
        }
    }

    /**
     * @param $cond
     * @return bool
     */
    public function delete($cond)
    {
        if (empty($cond)) {
            return false;
        }
        return pdo_delete($this->tablename, $cond);
    }

    /**
     * @param $data
     * @param $cond
     * @return bool
     */
    public function update($data, $cond)
    {
        if (empty($data) || empty($cond)) {
            return false;
        }
        return pdo_update($this->tablename, $data, $cond);
    }
}