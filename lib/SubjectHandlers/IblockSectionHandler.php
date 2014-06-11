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
        $res->setSuccess(true);
        if (!\CIBlockSection::GetByID($data['ID'])->Fetch()) {
            /** @var $DB \CDatabase */
            global $DB;
            $res
                ->setSuccess((bool)$DB->Add('b_iblock_section', array(
                        'ID' => $data['ID'],
                        'IBLOCK_ID' => $data['IBLOCK_ID'],
                        'GLOBAL_ACTIVE' => $data['GLOBAL_ACTIVE'],
                        'LEFT_MARGIN' => $data['LEFT_MARGIN'],
                        'RIGHT_MARGIN' => $data['RIGHT_MARGIN'],
                        'DATE_CREATE' => $data['DATE_CREATE'],
                        'CREATED_BY' => $data['CREATED_BY']
                    )
                ))
                ->setMessage($DB->GetErrorMessage());
        }
        if (!$res->isSuccess()) {
            return $res;
        }
        $res
            ->setSuccess((bool)$sec->Update($data['ID'], $data))
            ->setMessage($sec->LAST_ERROR);
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