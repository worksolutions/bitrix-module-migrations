<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;


use WS\Migrations\ApplyResult;
use WS\Migrations\Localization;
use WS\Migrations\Module;
use WS\Migrations\Reference\ReferenceController;
use WS\Migrations\Reference\ReferenceItem;

abstract class BaseSubjectHandler {
    /**
     * @var ReferenceController
     */
    private $_referenceController;


    static public function className() {
        return get_called_class();
    }

    final public function __construct(ReferenceController $referenceController) {
        $this->_referenceController = $referenceController;
    }

    /**
     * Return entities identifiers
     * @return mixed
     */
    abstract public function existsIds();

    /**
     * @return ReferenceController
     */
    public function getReferenceController() {
        return $this->_referenceController;
    }

    /**
     * Регистрация идентификатора для текущей версии
     * @param $id
     * @param null $referenceValue
     */
    protected function registerCurrentVersionId($id, $referenceValue = null) {
        $item = new ReferenceItem();
        $item->group = $this->getSubjectGroup();
        $item->id = $id;
        $referenceValue && $item->reference = $referenceValue;
        $this->getReferenceController()->registerItem($item);
    }

    protected function hasCurrentReference($id) {
        return $this->getReferenceController()->hasItemId($id, $this->getSubjectGroup());
    }

    protected function getIdByVersion($id, $dbVersion = null) {
        return $this->getReferenceController()->getItemId($id, $this->getSubjectGroup(), $dbVersion);
    }

    protected function getReferenceValue($id, $dbVersion = null) {
        return $this->getReferenceController()->getReferenceValue($id, $this->getSubjectGroup(), $dbVersion);
    }

    protected function getCurrentVersionId($id, $dbVersion) {
        return $this->getReferenceController()->getCurrentIdByOtherVersion($id, $this->getSubjectGroup(), $dbVersion);
    }

    protected function getCurrentIdByReference($reference) {
        $item = $this->getReferenceController()->getItemCurrentVersionByReference($reference);
        return $item->id;
    }

    protected function removeCurrentVersion($id) {
        return
            $this->getReferenceController()->removeCurrentVersion($id, $this->getSubjectGroup());
    }

    abstract protected function getExistsSubjectIds();

    public function registerExistsReferences() {
        $ids = $this->getExistsSubjectIds();
        foreach ($ids as $id) {
            $this->registerCurrentVersionId($id);
        }
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
     * @param $dbVersion
     * @return ApplyResult
     */
    abstract public function delete($id, $dbVersion = null);

    /**
     * Get snapshot from database
     * @param $id
     * @param null $dbVersion
     * @return mixed
     */
    abstract public function getSnapshot($id, $dbVersion = null);

    /**
     * Apply snapshot to database
     * @param $data
     * @param $dbVersion
     * @return ApplyResult
     */
    abstract public function applySnapshot($data, $dbVersion = null);

    /**
     * Get identifier record by snapshot from database
     * @param array $data
     * @return mixed
     */
    public function getIdBySnapshot($data = array()) {
        return $data['ID'];
    }

    /**
     * Inject identifier in snapshot data
     * @param $id
     * @param $data
     * @return array Data
     */
    public function injectIdInSnapshotData($id, $data) {
        $data['ID'] = $id;
        return $data;
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
     * @param $dbVersion
     * @return ApplyResult
     */
    public function applyChanges($data, $dbVersion) {
        return $this->applySnapshot($data, $dbVersion);
    }

    protected function handleNullValues($data) {
        foreach ($data as & $value) {
            if (is_null($value)) {
                $value = false;
            }
        }
        return $data;
    }

    /**
     * Признак обязательного наличия в миграциях проекта
     * @return bool
     */
    public function required() {
        return true;
    }

    abstract protected  function getSubjectGroup();
}
