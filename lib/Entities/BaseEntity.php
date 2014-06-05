<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Entities;


abstract class BaseEntity {
    public $id;

    private $_isNew = true;

    /**
     * @param $props
     * @return $this
     */
    static public function create($props) {
        /** @var $model BaseEntity */
        $model = new static;
        foreach ($props as $name => $value) {
            $model->{$name} = $value;
        }
        $model->_isNew = false;
        return $model;
    }

    /**
     * @param $fields
     * @return $this
     */
    static private function _createByRow($fields) {
        $props = array();
        $fieldsToProps = array_flip(static::map());
        foreach ($fields as $name => $value) {
            $name = $fieldsToProps[$name];
            $props[$name] = $value;
        }
        $props = static::modifyFromDb($props);
        return self::create($props);
    }

    private function _getRawFields() {
        $result = array();
        $data = array();
        foreach (static::map() as $property => $field) {
            $data[$property] = $this->{$property};
        }
        $data = static::modifyToDb($data);
        foreach (static::map() as $property => $field) {
            $result[$field] = $data[$property];
        }

        return $result;
    }

    /**
     * @param array $params
     * @return AppliedChangesLogModel[]
     */
    static public function find($params = array()) {
        $modelToDb = static::map();
        $fReplaceList = function ($list) use ($modelToDb) {
            return array_map(function ($item) use ($modelToDb) {
                return $modelToDb[$item];
            }, $list);
        };

        if ($params['select']) {
            $params['select'] = $fReplaceList($params['select']);
        }
        if ($params['group']) {
            $pGroup = array();
            foreach ($params['group'] as $field => $value) {
                $pGroup[$modelToDb[$field]] = $value;
            }
            $params['group'] = $pGroup;
        }

        if ($params['filter']) {
            $pFilter = array();
            foreach ($params['filter'] as $field => $value) {
                $field = preg_replace_callback("/\w+/", function ($matches) use ($modelToDb) {
                    return $modelToDb[$matches[0]];
                }, $field);
                $pFilter[$field] = $value;
            }
            $params['filter'] = $pFilter;
        }
        $dbResult = static::callGatewayMethod('getList', $params);
        $rows = $dbResult->fetchAll();
        $items = array();
        foreach ($rows as $row) {
            $items[] = self::_createByRow($row);
        }
        return $items;
    }

    /**
     * @param array $params
     * @return $this
     */
    static public function findOne($params = array()) {
        $params['limit'] = 1;
        $items = self::find($params);
        return $items[0];
    }

    /**
     * @param $name
     * @param $p1
     * @param $p2
     * @param $p3
     *
     * @return mixed
     */
    static public function callGatewayMethod($name) {
        $params = func_get_args();
        $name = array_shift($params);
        return call_user_func_array(array(static::gatewayClass(), $name), $params);
    }

    public function delete() {
        $res = static::callGatewayMethod('delete', $this->id);
        return !(bool)$res->getErrors();
    }

    public function insert() {
        $res = static::callGatewayMethod('add', $this->_getRawFields());
        $this->id = $res->getId();
        return !(bool)$res->getErrors();
    }

    public function update() {
        $res = static::callGatewayMethod('update', $this->id, $this->_getRawFields());
        return !(bool)$res->getErrors();
    }

    public function save() {
        return $this->_isNew ? $this->insert() : $this->update();
    }

    abstract static protected function map();

    abstract static protected function gatewayClass();

    static protected function modifyFromDb($data) {
        return $data;
    }

    static protected function modifyToDb($data) {
        return $data;
    }
}