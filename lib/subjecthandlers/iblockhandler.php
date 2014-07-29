<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace ws\migrations\SubjectHandlers;


use Bitrix\Iblock\IblockTable;
use WS\Migrations\ApplyResult;
use WS\Migrations\Module;
use WS\Migrations\Reference\ReferenceController;

class IblockHandler extends BaseSubjectHandler  {

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        return $this->getLocalization()->getDataByPath('iblock.name');
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

    public function getIdBySnapshot($data = array()) {
        return $data['iblock']['ID'];
    }

    public function injectIdInSnapshotData($id, $data) {
        $data['iblock']['ID'] = $id;
        return $data;
    }

    /**
     * @param $id
     * @param null $dbVersion
     * @return array|mixed
     */
    public function getSnapshot($id, $dbVersion = null) {
        if (!$id) {
            return false;
        }
        $dbVersion && $id = $this->getCurrentVersionId($id, $dbVersion);
        !$dbVersion && !$this->hasCurrentReference($id) && $this->registerCurrentVersionId($id);

        $iblock = \CIBlock::GetArrayByID($id);
        if (!$iblock) {
            return false;
        }
        $type = \CIBlockType::GetByID($iblock['IBLOCK_TYPE_ID'])->Fetch();
        return array(
            'iblock' => $iblock,
            'type' => $type
        );
    }

    public function applySnapshot($data, $dbVersion = null) {
        $iblockData = $this->handleNullValues($data['iblock']);
        $typeData = $this->handleNullValues($data['type']);

        $res = new ApplyResult();
        $type = new \CIBlockType();
        if (!\CIBlockType::GetByID($typeData['ID'])->Fetch()) {
            $res
                ->setSuccess($type->Add($typeData));
        } else {
            $res
                ->setSuccess($type->Update($typeData['ID'], $typeData));
        }

        if (!$res->isSuccess()) {
            return $res->setMessage($type->LAST_ERROR);
        }
        $extId = $iblockData['ID'];
        if ($dbVersion) {
            $id = $this->getCurrentVersionId($extId, $dbVersion);
        } else {
            $id = $extId;
        }

        if (!$dbVersion && !IblockTable::getById($id)->fetch()) {
            $addRes = IblockTable::add(array('ID' => $id, 'IBLOCK_TYPE_ID' => $typeData['ID'], 'NAME' => 'add'));
            if (!$addRes->isSuccess()) {
                throw new \Exception('add iblock error ' . implode(', ', $addRes->getErrorMessages()));
            }
        }

        $iblock = new \CIBlock();
        if ($id && IblockTable::getById($id)->fetch()) {
            $res->setSuccess((bool)$iblock->Update($id, $iblockData));
        } else {
            $res->setSuccess((bool)($id = $iblock->Add($iblockData)));
            $this->registerCurrentVersionId($id, $this->getReferenceValue($extId, $dbVersion));
        }
        $res->setId($id);
        return $res->setMessage($iblock->LAST_ERROR);
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
        return $res->setSuccess((bool)\CIBlock::Delete($id))
            ->setMessage('Not execute ib delete');
    }

    protected function getSubjectGroup() {
        return ReferenceController::GROUP_IBLOCK;
    }

    public function existsIds() {
        $dbRes = IblockTable::getList(array(
            'select' => array('ID')
        ));
        $res = array();
        while ($item = $dbRes->fetch()) {
            $res[] = $item['ID'];
        }
        return $res;
    }

    protected function getExistsSubjectIds() {
        $rs = IblockTable::getList(array(
            'select' => array('ID')
        ));
        $res = array();
        while ($arIblock = $rs->fetch()) {
            $res[] = $arIblock['ID'];
        }
        return $res;
    }
}