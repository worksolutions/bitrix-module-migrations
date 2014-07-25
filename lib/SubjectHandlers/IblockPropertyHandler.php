<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;

use Bitrix\Iblock\PropertyTable;
use WS\Migrations\ApplyResult;
use WS\Migrations\Module;
use WS\Migrations\Reference\ReferenceController;

class IblockPropertyHandler extends BaseSubjectHandler {

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
        return \CIBlockProperty::GetByID($id)->Fetch();
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
            if (!$id) {
                $referenceValue = $this->getReferenceValue($extId, $dbVersion);
            }
        } else {
            $id = $extId;
        }
        if (!$dbVersion && !PropertyTable::getList(array('filter' => array('=ID' => $id)))->fetch()) {
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
        if ($id) {
            $res->setSuccess((bool) $prop->Update($id, $data));
        } else {
            $res->setSuccess((bool) ($id = $prop->Add($data)));
            $this->registerCurrentVersionId($id, $referenceValue);
        }
        $res->setId($id);
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
        return $res->setSuccess((bool)\CIBlockProperty::Delete($id));
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