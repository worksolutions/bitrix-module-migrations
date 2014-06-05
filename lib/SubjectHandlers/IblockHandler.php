<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;


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


    public function getSnapshot($id) {
        $iblock = \CIBlock::GetArrayByID($id);
        $type = \CIBlockType::GetByID($iblock['IBLOCK_TYPE_ID'])->Fetch();
        return array(
            'iblock' => $iblock,
            'type' => $type
        );
    }

    public function applySnapshot($data) {
        $iblockData = $data['iblock'];
        $typeData = $data['type'];

        $type = new \CIBlockType();
        $res = false;
        if (!\CIBlockType::GetByID($typeData['ID'])->Fetch()) {
            $res = $type->Add($typeData);
        } else {
            $res = $type->Update($typeData['ID'], $typeData);
        }
        if (!$res) {
            return false;
        }
        $iblock = new \CIBlock();
        if (!\CIBlock::GetArrayByID($iblockData['ID'])) {
            $res = $iblock->Add($iblockData);
        } else {
            $res = $iblock->Update($iblockData['ID'], $iblockData);
        }
        return $res;
    }

    /**
     * Delete subject record
     * @param $id
     * @return mixed
     */
    public function delete($id) {
        $iblock = new \CIBlock();
        return $iblock->Delete($id);
    }
}