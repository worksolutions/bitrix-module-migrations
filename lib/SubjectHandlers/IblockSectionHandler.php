<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\SubjectHandlers;


use Bitrix\Iblock\SectionTable;
use WS\Migrations\ApplyResult;
use WS\Migrations\Module;
use WS\Migrations\Reference\ReferenceController;

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
                return $data[0]['ID'];
        }
        return null;
    }

    public function getSnapshot($id, $dbVersion = null) {
        if (!$id) {
            return false;
        }
        $dbVersion && $id = $this->getCurrentVersionId($id, $dbVersion);
        !$dbVersion && !$this->hasCurrentReference($id) && $this->registerCurrentVersionId($id);
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

        $extId = $data['ID'];
        if ($dbVersion) {
            $data['IBLOCK_ID'] = $this->getReferenceController()->getCurrentIdByOtherVersion($data['IBLOCK_ID'], ReferenceController::GROUP_IBLOCK, $dbVersion);
            $id = $this->getCurrentVersionId($extId, $dbVersion);
            if (!$id) {
                $referenceValue = $this->getReferenceValue($extId, $dbVersion);
            }
        }
        if ($id) {
            $res->setSuccess((bool)$sec->Update($id, $data));
        } else {
            $res->setSuccess((bool) ($id = $sec->Add($data)));
            $this->registerCurrentVersionId($id, $referenceValue);
        }
        $res->setId($id);
        $res->setMessage($sec->LAST_ERROR);
        return $res;
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


        $sec = new \CIBlockSection();
        $res = new ApplyResult();
        return $res
            ->setSuccess((bool) $sec->Delete($id))
            ->setMessage($sec->LAST_ERROR);
    }

    protected function getSubjectGroup() {
        return ReferenceController::GROUP_IBLOCK_SECTION;
    }

    public function existsIds() {
        $dbRes = SectionTable::getList(array(
            'select' => array('ID')
        ));
        $res = array();
        while ($item = $dbRes->fetch()) {
            $res[] = $item['ID'];
        }
        return $res;
    }
}