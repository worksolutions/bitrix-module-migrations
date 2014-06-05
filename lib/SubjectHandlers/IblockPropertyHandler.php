<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;

use WS\Migrations\Module;

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

    public function getSnapshot($id) {
        return \CIBlockProperty::GetByID($id)->Fetch();
    }

    public function applySnapshot($data) {
        $prop = new \CIBlockProperty();
        $res = false;
        if (\CIBlockProperty::GetByID($data['ID'])->Fetch()) {
            $res = $prop->Add($data);
        } else {
            $res = $prop->Update($data['ID'], $data);
        }
        return $res;
    }

    /**
     * Delete subject record
     * @param $id
     * @return mixed
     */
    public function delete($id) {
        $prop = new \CIBlockProperty();
        return $prop->Delete($id);
    }
}