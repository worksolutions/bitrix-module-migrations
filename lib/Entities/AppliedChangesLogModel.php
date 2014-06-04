<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Entities;


use Bitrix\Main\Type\DateTime;

class AppliedChangesLogModel {
    public
        $id, $groupLabel, $date,
        $processName, $subjectName, $updateData,
        $originalData, $description;

    private $_isNew = true;

    private static $map = array(
        'id' => 'ID',
        'groupLabel' => 'GROUP_LABEL',
        'date' => 'DATE',
        'processName' => 'PROCESS',
        'subjectName' => 'SUBJECT',
        'updateData' => 'UPDATE_DATA',
        'originalData' => 'ORIGINAL_DATA',
        'description' => 'DESCRIPTION'
    );

    public function __construct() {
        $this->date = new \DateTime();
    }

    /**
     * @param $props
     * @return AppliedChangesLogModel
     */
    static public function create($props) {
        /** @var $model AppliedChangesLogModel */
        $model = new static;
        foreach ($props as $name => $value) {
            $model->{$name} = $value;
        }
        $model->_isNew = false;
        return $model;
    }

    /**
     * @param $fields
     * @return AppliedChangesLogModel
     */
    static private function _createByRow($fields) {
        $props = array();
        $fieldsToProps = array_flip(self::$map);
        foreach ($fields as $name => $value) {
            $name = $fieldsToProps[$name];
            if ($name == 'date') {
                if ($value instanceof DateTime) {
                    $value = $value->getValue();
                } else {
                    $value = new \DateTime($value);
                }
            }
            if (in_array($name, array('originalData', 'updateData'))) {
                $value = json_decode($value, true);
            }
            $props[$name] = $value;
        }
        return self::create($props);
    }

    private function _getRawFields() {
        $result = array();
        foreach (self::$map as $property => $field) {
            $value = $this->{$property};
            if ($property == 'date' && $value instanceof \DateTime) {
                $value = DateTime::createFromPhp($value);
            }
            if (in_array($property, array('originalData', 'updateData'))) {
                $value = json_encode($value);
            }
            $result[$field] = $value;
        }
        return $result;
    }

    /**
     * @param array $params
     * @return AppliedChangesLogModel[]
     */
    static public function find($params = array()) {
        $modelToDb = static::$map;
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
                preg_replace_callback("/w+/", function ($matches) use ($modelToDb) {
                    return $modelToDb[$matches[0]];
                }, $field);
                $pFilter[$modelToDb[$field]] = $value;
            }
            $params['filter'] = $pFilter;
        }
        $dbResult = AppliedChangesLogTable::getList($params);
        $rows = $dbResult->fetchAll();
        $items = array();
        foreach ($rows as $row) {
            $items[] = self::_createByRow($row);
        }
        return $items;
    }

    /**
     * @param array $params
     * @return AppliedChangesLogModel|null
     */
    static public function findOne($params = array()) {
        $params['limit'] = 1;
        $items = self::find($params);
        return $items[0];
    }

    public function delete() {
        $res = AppliedChangesLogTable::delete($this->id);
        return (bool)$res->getErrors();
    }

    public function insert() {
        $res = AppliedChangesLogTable::add($this->_getRawFields());
        return (bool)$res->getErrors();
    }

    public function update() {
        $res = AppliedChangesLogTable::update($this->id, $this->_getRawFields());
        return (bool)$res->getErrors();
    }

    public function save() {
        return $this->_isNew ? $this->insert() : $this->update();
    }
}