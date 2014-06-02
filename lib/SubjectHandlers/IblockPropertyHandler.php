<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers;


use WS\Migrations\SubjectHandlers\BaseSubjectHandler;

class IblockPropertyHandlerBase extends BaseSubjectHandler {

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        $this->getLocalization()->getDataByPath('iblockProperty.name');
    }

    public function getIdByChangeMethod($method, $data = array()) {
        switch ($method) {
            case Module::FIX_CHANGES_ADD_KEY:
            case Module::FIX_CHANGES_BEFORE_CHANGE_KEY:
            case Module::FIX_CHANGES_AFTER_CHANGE_KEY:
                return $data[0]['ID'];
            case Module::FIX_CHANGES_DELETE_KEY:
                return $data[0];
        }
        return null;
    }

    public function getSnapshot($id) {
        return \CIBlockProperty::GetByID($id)->Fetch();
    }

    public function applySnapshot($data) {
        $prop = new \CIBlockProperty();
        if (\CIBlockProperty::GetByID($data['ID'])->Fetch()) {
            $prop->Add($data);
        } else {
            $prop->Update($data['ID'], $data);
        }
    }
}