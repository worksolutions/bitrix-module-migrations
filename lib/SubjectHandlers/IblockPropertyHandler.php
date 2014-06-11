<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;

use WS\Migrations\ApplyResult;
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

    /**
     * @param $data
     * @return ApplyResult
     */
    public function applySnapshot($data) {
        $data = $this->handleNullValues($data);
        global $DB;
        $prop = new \CIBlockProperty();
        $res = new ApplyResult();
        $res->setSuccess(true);
        if (!$arIblock = \CIBlock::GetArrayByID($data['IBLOCK_ID'])) {
            return $res
                ->setSuccess(false)
                ->setMessage($this->getLocalization()->message('iblockProperty.errors.iblockNotExists', array(':id:' => $data['IBLOCK_ID'])));
        }
        $isTwoVersion = $arIblock['VERSION'] == 2;
        $data['VERSION'] = $arIblock['VERSION'];

        if (!$DB->Query("select ID from b_iblock_property where ID=".((int) $data['ID']))->Fetch()) {
            /** @var $DB \CDatabase */
            $propAddResult = $DB->Add("b_iblock_property", $data);
            $res->setSuccess((bool)$propAddResult)->setMessage($DB->GetErrorMessage());
            if ($propAddResult && $isTwoVersion) {
                $twoVersionAddResult = $prop->_Add($data['ID'], $data);
                $res
                    ->setSuccess($twoVersionAddResult)
                    ->setMessage($DB->GetErrorMessage());
                !$twoVersionAddResult && $prop->Delete($data['ID']);
            }
        }
        if (!$res->isSuccess()) {
            return $res;
        }
        $res->setSuccess((bool) $prop->Update($data['ID'], $data));
        return $res->setMessage($prop->LAST_ERROR);
    }

    /**
     * Delete subject record
     * @param $id
     * @return ApplyResult
     */
    public function delete($id) {
        $prop = new \CIBlockProperty();
        $res = new ApplyResult();
        return $res
                ->setSuccess((bool)$prop->Delete($id))
                ->setMessage($prop->LAST_ERROR);
    }
}