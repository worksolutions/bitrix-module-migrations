<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;


use WS\Migrations\ApplyResult;
use WS\Migrations\Module;
use WS\Migrations\Reference\ReferenceController;
use WS\Migrations\Reference\ReferenceItem;

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
     * @param null $dbVersion
     * @return ApplyResult
     */
    public function applySnapshot($data, $dbVersion = null) {
        $data = $this->handleNullValues($data);
        $sec = new \CIBlockSection();
        $res = new ApplyResult();

        $id = $data['ID'];
        if ($dbVersion) {
            $data['IBLOCK_ID'] = $this->getReferenceController()->getItemIdByOtherVersion($dbVersion, $data['IBLOCK_ID'], ReferenceController::GROUP_IBLOCK);
            $id = $this->getReferenceController()->getItemIdByOtherVersion($dbVersion, $id, ReferenceController::GROUP_IBLOCK_SECTION);
            if (!$id) {
                $referenceValue = $this->getReferenceController()->getReferenceValueByOtherVersion($dbVersion, $id, ReferenceController::GROUP_IBLOCK_SECTION);
            }
        }
        if ($id) {
            $res->setSuccess((bool)$sec->Update($id, $data));
        } else {
            $res->setSuccess((bool) ($id = $sec->Add($id, $data)));
            $referenceItem = new ReferenceItem();
            $referenceItem->id = $id;
            $referenceItem->group = ReferenceController::GROUP_IBLOCK_SECTION;
            $referenceItem->reference = $referenceValue;
            $this->getReferenceController()->registerItem($referenceItem);
        }
        $res->setId($id);

        $res
            ->setMessage($sec->LAST_ERROR);
        return $res;
    }

    /**
     * Delete subject record
     * @param $id
     * @param null $dbVersion
     * @return ApplyResult
     */
    public function delete($id, $dbVersion = null) {
        $dbVersion && $id = $this->getReferenceController()->getItemIdByOtherVersion($dbVersion, $id, ReferenceController::GROUP_IBLOCK_SECTION);
        $sec = new \CIBlockSection();
        $res = new ApplyResult();
        return $res
            ->setSuccess((bool) $sec->Delete($id))
            ->setMessage($sec->LAST_ERROR);
    }
}