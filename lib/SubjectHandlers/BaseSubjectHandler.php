<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;


use WS\Migrations\ApplyResult;
use WS\Migrations\Module;

abstract class BaseSubjectHandler {
    static public function className() {
        return get_called_class();
    }

    /**
     * @return Localization
     */
    protected function getLocalization() {
        return Module::getInstance()->getLocalization('handlers');
    }

    /**
     * Name of Handler in Web interface
     * @return string
     */
    abstract public function getName();

    /**
     * @param $method
     * @param array $data
     * @return scalar
     */
    abstract public function getIdByChangeMethod($method, $data = array());

    /**
     * Delete subject record
     * @param $id
     * @return ApplyResult
     */
    abstract public function delete($id);

    /**
     * Get snapshot from database
     * @param $id
     * @return mixed
     */
    abstract public function getSnapshot($id);

    /**
     * Apply snapshot to database
     * @param $data
     * @return ApplyResult
     */
    abstract public function applySnapshot($data);

    /**
     * Get identifier record by snapshot from database
     * @param array $data
     * @return mixed
     */
    public function getIdBySnapshot($data = array()) {
        return $data['ID'];
    }

    /**
     * Analysis changes (diff) by two snapshots
     * @param $updatedData
     * @param null $baseData
     * @return mixed
     */
    public function analysisOfChanges($updatedData, $baseData = null) {
        return $updatedData;
    }

    /**
     * Apply changes, run after analysis
     * @param $data
     * @return ApplyResult
     */
    public function applyChanges($data) {
        return $this->applySnapshot($data);
    }

    protected function handleNullValues($data) {
        foreach ($data as $key => & $value) {
            if (is_null($value)) {
                $value = false;
            }
        }
        return $data;
    }

}
