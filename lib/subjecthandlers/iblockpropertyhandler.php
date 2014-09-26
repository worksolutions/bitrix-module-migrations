<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;

use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use WS\Migrations\ApplyResult;
use WS\Migrations\Module;
use WS\Migrations\Reference\ReferenceController;
use WS\Migrations\Reference\ReferenceItem;

class IblockPropertyHandler extends BaseSubjectHandler {

    const LIST_TYPE_SIGN = 'L';

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        return $this->getLocalization()->getDataByPath('iblockProperty.name');
    }

    public function getIdByChangeMethod($method, $data = array()) {
        switch ($method) {
            case Module::FIX_CHANGES_ADD_KEY:
            case Module::FIX_CHANGES_BEFORE_CHANGE_KEY:
            case Module::FIX_CHANGES_AFTER_CHANGE_KEY:
                return $data[0]['ID'];
            case Module::FIX_CHANGES_BEFORE_DELETE_KEY:
            case Module::FIX_CHANGES_AFTER_DELETE_KEY:
                return $data[0];
        }
        return null;
    }

    public function getSnapshot($id, $dbVersion = null) {
        if (!$id) {
            return false;
        }
        $dbVersion && $id = $this->getCurrentVersionId($id, $dbVersion);
        !$dbVersion && !$this->hasCurrentReference($id) && $this->registerCurrentVersionId($id);
        $data = PropertyTable::GetByID($id)->Fetch();
        $data['~reference'] = $this->getReferenceValue($id);
        $data['PROPERTY_TYPE'] == self::LIST_TYPE_SIGN && $data['~property_list_values'] = $this->_getListTypeValues($id);
        return $data;
    }

    private function _applyPropertyListTypeValues($id, $values) {
        $addValues = array();
        $updateValues = array();
        $useValuesIds = array();

        foreach ($values as $value) {
            $value['PROPERTY_ID'] = $id;
            try {
                $value['ID'] = $this->getReferenceController()->getItemCurrentVersionByReference($value['~reference'])->id;
                $useValuesIds[] = $value['ID'];
                $updateValues[] = $value;
            } catch (\Exception $e) {
                $addValues[] = $value;
            }
        }
        $currentValues = PropertyEnumerationTable::getList(array('filter' => array('=PROPERTY_ID' => $id)))->fetchAll();
        foreach ($currentValues as $value) {
            !in_array($value['ID'], $useValuesIds) && PropertyEnumerationTable::delete(array('ID' => $value['ID'], 'PROPERTY_ID' => $value['PROPERTY_ID']));
        }
        foreach ($addValues as $value) {
            unset($value['ID']);
            unset($value['~reference']);
            $result = PropertyEnumerationTable::add($value);
            if (!$result->getId()) {
                throw new \Exception('Add property list value. Property not save. '.var_export($result->getErrorMessages(), true));
            }
            $referenceItem = new ReferenceItem();
            $referenceItem->id = $result->getId();
            $referenceItem->group =  ReferenceController::GROUP_IBLOCK_PROPERTY_LIST_VALUES;
            $referenceItem->reference = $value['~reference'];
            $this->getReferenceController()->registerItem($referenceItem);
        }

        foreach ($updateValues as $value) {
            $vId = $value['ID'];
            unset($value['ID']);
            unset($value['~reference']);
            $result = PropertyEnumerationTable::update(array('ID' => $vId, 'PROPERTY_ID' => $value['PROPERTY_ID']), $value);
            if (!$result->isSuccess()) {
                throw new \Exception('Update property list value. Property not save. '.var_export($result->getErrorMessages(), true));
            }
        }
    }

    private function _getListTypeValues($id) {
        $dbRes = PropertyEnumerationTable::getList(array(
            'filter' => array(
                '=PROPERTY_ID' => $id
            )
        ));
        $items = $dbRes->fetchAll();
        foreach ($items as & $item) {
            try {
                $item['~reference'] = $this
                    ->getReferenceController()
                    ->getReferenceValue($item['ID'], ReferenceController::GROUP_IBLOCK_PROPERTY_LIST_VALUES);
            } catch (\Exception $e) {
                $referenceItem = new ReferenceItem();
                $referenceItem->id = $item['ID'];
                $referenceItem->group =  ReferenceController::GROUP_IBLOCK_PROPERTY_LIST_VALUES;
                $this->getReferenceController()->registerItem($referenceItem);
                $item['~reference'] = $referenceItem->reference;
            }
        }
        return $items;
    }

    /**
     * @param $data
     * @param null $dbVersion
     * @throws \Exception
     * @return ApplyResult
     */
    public function applySnapshot($data, $dbVersion = null) {
        $data = $this->handleNullValues($data);
        $prop = new \CIBlockProperty();
        $res = new ApplyResult();
        $extId = $data['ID'];
        if ($dbVersion) {
            $data['IBLOCK_ID'] = $this->getReferenceController()->getCurrentIdByOtherVersion($data['IBLOCK_ID'], ReferenceController::GROUP_IBLOCK, $dbVersion);
            $id = $this->getCurrentVersionId($extId, $dbVersion);
        } else {
            $id = $extId;
        }
        if (!$dbVersion && !PropertyTable::getById($id)->fetch()) {
            unset($data['VERSION']);
            $addRes = PropertyTable::add(array(
                'ID' => $id,
                'NAME' => $data['NAME'],
                'IBLOCK_ID' => $data['IBLOCK_ID']
            ));
            if (!$addRes->isSuccess()) {
                throw new \Exception('Ќе удалось возобновить свойство текущей версии. ' . implode(', ', $addRes->getErrorMessages())."\n".var_export($data, true));
            }
        }
        if ($id && PropertyTable::getById($id)->fetch()) {
            $res->setSuccess((bool) $prop->Update($id, $data));
        } else {
            $res->setSuccess((bool) ($id = $prop->Add($data)));
            $this->registerCurrentVersionId($id, $this->getReferenceValue($extId, $dbVersion));
        }
        $res->setId($id);
        if ($data['PROPERTY_TYPE'] == self::LIST_TYPE_SIGN && !empty($data['~property_list_values'])) {
            $this->_applyPropertyListTypeValues($id, $data['~property_list_values']);
        }
        return $res->setMessage($prop->LAST_ERROR);
    }

    /**
     * Delete subject record
     * @param $id
     * @param null $dbVersion
     * @return ApplyResult
     */
    public function delete($id, $dbVersion = null) {
        $dbVersion && $id = $this->getCurrentVersionId($id, $dbVersion);
        !$dbVersion && !$this->hasCurrentReference($id) && $this->registerCurrentVersionId($id);
        $res = new ApplyResult();
        $res->setSuccess((bool)\CIBlockProperty::Delete($id));
        $res->isSuccess() && $this->removeCurrentVersion($id);
        return $res;
    }

    protected function getSubjectGroup() {
        return ReferenceController::GROUP_IBLOCK_PROPERTY;
    }

    /**
     * Return entities identifiers
     * @return mixed
     */
    public function existsIds() {
        $dbRes = PropertyTable::getList(array(
            'select' => array('ID')
        ));
        $res = array();
        while ($item = $dbRes->fetch()) {
            $res[] = $item['ID'];
        }
        return $res;
    }

    protected function getExistsSubjectIds() {
        $rs = PropertyTable::getList(array(
            'select' => array('ID')
        ));
        $res = array();
        while ($arProperty = $rs->fetch()) {
            $res[] = $arProperty['ID'];
        }
        return $res;
    }
}