<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;


use WS\Migrations\Module;

class IblockSectionHandler extends BaseSubjectHandler {

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        $this->getLocalization()->getDataByPath('iblockSection.name');
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

    public function applySnapshot($data) {
        $sec = new \CIBlockSection();
        if (\CIBlockSection::GetByID($data['ID'])->Fetch()) {
            $sec->Add($data);
        } else {
            $sec->Update($data['ID'], $data);
        }
    }
}