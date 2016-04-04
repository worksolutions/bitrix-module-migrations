<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;

use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Type\DateTime;
use WS\Migrations\ApplyResult;
use WS\Migrations\Diagnostic\DiagnosticResult;
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
            case Module::FIX_CHANGES_AFTER_ADD_KEY:
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

    private function _applyPropertyListTypeValues($propertyId, $values) {
        /** @var \CDatabase $DB */
        global $DB;

        $addValues = array();
        $updateValues = array();
        $useValuesIds = array();

        foreach ($values as $value) {
            $value['PROPERTY_ID'] = $propertyId;
            try {
                $value['ID'] = $this->getReferenceController()->getItemCurrentVersionByReference($value['~reference'])->id;
                $hasValue = PropertyEnumerationTable::getByPrimary(
                    array('ID' => $value['ID'], 'PROPERTY_ID' => $value['PROPERTY_ID'])
                )->getSelectedRowsCount() > 0;
                if (!$hasValue) {
                    throw new \Exception("Record not exists");
                }
                $useValuesIds[] = $value['ID'];
                $updateValues[] = $value;
            } catch (\Exception $e) {
                $addValues[] = $value;
            }
        }
        $currentValues = PropertyEnumerationTable::getList(array('filter' => array('=PROPERTY_ID' => $propertyId)))->fetchAll();
        foreach ($currentValues as $value) {
            if (in_array($value['ID'], $useValuesIds)) {
                continue;
            }
            PropertyEnumerationTable::delete(array('ID' => $value['ID'], 'PROPERTY_ID' => $value['PROPERTY_ID']));
            $this->getReferenceController()->removeReference($value['ID'], ReferenceController::GROUP_IBLOCK_PROPERTY_LIST_VALUES);
        }

        $enum = new \CIBlockPropertyEnum();
        foreach ($addValues as $value) {
            $valueReference = $value['~reference'];
            unset($value['~reference']);
            if ($value['ID'] && !PropertyEnumerationTable::getList(array('filter' => array('ID' => $value['ID'])))->fetch()) {
                $enumElementId = $value['ID'];
                PropertyEnumerationTable::add($value);
            } else {
                unset($value['ID']);
                $enumElementId = $enum->Add($value);
            }
            if (!$enumElementId) {
                throw new \Exception('Add property list value. Property not save. '.var_export($value, true));
            }
            unset($value['XML_ID']);
            $result = PropertyEnumerationTable::update(array('ID' => $enumElementId, 'PROPERTY_ID' => $value['PROPERTY_ID']), $value);
            if (!$result->isSuccess()) {
                throw new \Exception('Add property list value in table. Property not save. '.var_export($result->getErrorMessages(), true));
            }
            $this->_registerListValueReference($enumElementId, $valueReference);
        }

        foreach ($updateValues as $value) {
            $vId = $value['ID'];
            unset($value['ID']);
            unset($value['~reference']);
            $res = $enum->Update($vId, $value);
            if (!$res) {
                throw new \Exception('Update property list value. Property not save. '.var_export($DB->GetErrorMessage(), true));
            }
            unset($value['XML_ID']);
            $result = PropertyEnumerationTable::update(array('ID' => $vId, 'PROPERTY_ID' => $value['PROPERTY_ID']), $value);
            if (!$result->isSuccess()) {
                throw new \Exception('Update property list value. Property not save. '.var_export($result->getErrorMessages(), true));
            }
        }
    }

    private function _getListTypeValues($propertyId) {
        $dbRes = PropertyEnumerationTable::getList(array(
            'filter' => array(
                '=PROPERTY_ID' => $propertyId
            )
        ));
        $items = $dbRes->fetchAll();
        foreach ($items as & $item) {
            try {
                $item['~reference'] = $this
                    ->getReferenceController()
                    ->getReferenceValue($item['ID'], ReferenceController::GROUP_IBLOCK_PROPERTY_LIST_VALUES);
            } catch (\Exception $e) {
                $item['~reference'] = $this->_registerListValueReference($item['ID']);
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
            if(isset($data['LINK_IBLOCK_ID']) && !empty($data['LINK_IBLOCK_ID'])){
                $data['LINK_IBLOCK_ID'] = $this->getReferenceController()->getCurrentIdByOtherVersion($data['LINK_IBLOCK_ID'], ReferenceController::GROUP_IBLOCK, $dbVersion);
            }
        } else {
            $id = $extId;
        }
        if ($id && !PropertyTable::getById($id)->fetch()) {
            unset($data['VERSION']);
            $addRes = PropertyTable::add(array(
                'ID' => $id,
                'NAME' => $data['NAME'],
                'IBLOCK_ID' => $data['IBLOCK_ID'],
            ));
            if (!$addRes->isSuccess()) {
                throw new \Exception('Ќе удалось возобновить свойство текущей версии. ' . implode(', ', $addRes->getErrorMessages())."\n".var_export($data, true));
            }
        }
        if ($id && PropertyTable::getById($id)->fetch()) {
            $res->setSuccess((bool) $prop->Update($id, $data));
        } else {
            unset($data['TIMESTAMP_X']);
            $res->setSuccess((bool) ($id = $prop->Add($data)));
            $this->registerCurrentVersionId($id, $this->getReferenceValue($extId, $dbVersion));
        }
        $res->setId($id);
        if ($data['PROPERTY_TYPE'] == self::LIST_TYPE_SIGN && is_array($data['~property_list_values'])) {
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
        $enumValues = PropertyEnumerationTable::getList(array('filter' => array('=PROPERTY_ID' => $id)))->fetchAll();
        $res = new ApplyResult();
        $res->setSuccess((bool)\CIBlockProperty::Delete($id));
        if ($res->isSuccess()) {
            $res->isSuccess() && $this->removeReference($id);
            foreach ($enumValues as $enumValue) {
                $this->getReferenceController()->removeReference($enumValue['ID'], ReferenceController::GROUP_IBLOCK_PROPERTY_LIST_VALUES);
            }
        }
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

    /**
     * @return DiagnosticResult
     */
    public function diagnostic() {
        $propertyResultReference = $this->diagnosticByReference();
        $propertyResultItems = $this->diagnosticByItems(\CIBlockProperty::GetList());

        $propertyListResultReference = $this->diagnosticByReference(ReferenceController::GROUP_IBLOCK_PROPERTY_LIST_VALUES);
        $propertyListResultItems = $this->diagnosticByItems(PropertyEnumerationTable::getList(), ReferenceController::GROUP_IBLOCK_PROPERTY_LIST_VALUES);

        $success = $propertyResultReference->isSuccess() && $propertyResultItems->isSuccess()
            && $propertyListResultReference->isSuccess() && $propertyListResultItems->isSuccess();
        $messages = array_merge(
            $propertyResultReference->getMessages(),
            $propertyResultItems->getMessages(),
            $propertyListResultReference->getMessages(),
            $propertyListResultItems->getMessages()
        );
        return new DiagnosticResult($success, $messages);
    }

    static public function depends() {
        return array(
            IblockHandler::className()
        );
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

    protected function  registerSubsidiaryVersions($id, $referenceValue = null) {
        $this->_getListTypeValues($id);
        $dbRes = PropertyEnumerationTable::getList(array(
            'filter' => array(
                '=PROPERTY_ID' => $id
            )
        ));
        foreach ($dbRes->fetchAll() ?: array() as $item) {
            $this->_registerListValueReference($item['ID']);
        }
    }

    /**
     * Return string reference
     *
     * @param $valueId
     * @param string|null $reference
     * @return string
     * @throws \Exception
     */
    private function _registerListValueReference($valueId, $reference = null) {
        $refController = $this->getReferenceController();
        if ($refController->hasItemId($valueId, ReferenceController::GROUP_IBLOCK_PROPERTY_LIST_VALUES)) {
            $referenceItem = $refController->getItemById($valueId, ReferenceController::GROUP_IBLOCK_PROPERTY_LIST_VALUES);
            return $referenceItem->reference;
        }
        $referenceItem = new ReferenceItem();
        $referenceItem->id = $valueId;
        $referenceItem->group = ReferenceController::GROUP_IBLOCK_PROPERTY_LIST_VALUES;
        $reference && $referenceItem->reference = $reference;
        $refController->registerItem($referenceItem);
        return $referenceItem->reference;
    }
}