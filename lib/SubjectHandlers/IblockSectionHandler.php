<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;


use WS\Migrations\ApplyResult;
use WS\Migrations\Module;

class IblockSectionHandler extends BaseSubjectHandler {

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        return $this->getLocalization()->getDataByPath('iblockSection.name');
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

    public function getSnapshot($id) {
        return \CIBlockSection::GetByID($id)->Fetch();
    }

    /**
     * @param $data
     * @return ApplyResult
     */
    public function applySnapshot($data) {
        $data = $this->handleNullValues($data);
        $sec = new \CIBlockSection();
        $res = new ApplyResult();
        if (!\CIBlockSection::GetByID($data['ID'])->Fetch()) {
            $res->setSuccess((bool)$sec->Add($data));
        } else {
            $res->setSuccess((bool)$sec->Update($data['ID'], $data));
        }
        return $res;
    }

    /**
     * Delete subject record
     * @param $id
     * @return ApplyResult
     */
    public function delete($id) {
        $sec = new \CIBlockSection();
        $res = new ApplyResult();
        return $res
            ->setSuccess((bool) $sec->Delete($id))
            ->setMessage($sec->LAST_ERROR);
    }
}