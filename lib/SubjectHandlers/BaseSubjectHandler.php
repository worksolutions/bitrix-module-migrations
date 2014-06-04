<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;


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

    abstract public function getSnapshot($id);

    abstract public function applySnapshot($data);

    public function analysisOfChanges($updatedData, $baseData = null) {
        return $updatedData;
    }

    public function applyChanges($data) {
        $this->applySnapshot($data);
    }
}
