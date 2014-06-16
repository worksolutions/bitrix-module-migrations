<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;


use WS\Migrations\ApplyResult;
use WS\Migrations\Module;

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

    public function getSnapshot($id) {
        $iblock = \CIBlock::GetArrayByID($id);
        $type = \CIBlockType::GetByID($iblock['IBLOCK_TYPE_ID'])->Fetch();
        return array(
            'iblock' => $iblock,
            'type' => $type
        );
    }

    public function applySnapshot($data) {
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
        /** @var $DB \CDatabase */
        global $DB;
        if (!\CIBlock::GetArrayByID($iblockData['ID'])) {
            $res->setSuccess((bool)$DB->Add('b_iblock', array('ID' => $iblockData['ID'])));
        }
        if (!$res->isSuccess()) {
            return $res->setMessage($DB->GetErrorMessage());
        }
        $iblock = new \CIBlock();
        $res->setSuccess((bool)$iblock->Update($iblockData['ID'], $iblockData));
        return $res->setMessage($iblock->LAST_ERROR);
    }

    /**
     * Delete subject record
     * @param $id
     * @return ApplyResult
     */
    public function delete($id) {
        $iblock = new \CIBlock();
        $res = new ApplyResult();
        return $res->setSuccess((bool)$iblock->Delete($id))
            ->setMessage($iblock->LAST_ERROR);
    }
}