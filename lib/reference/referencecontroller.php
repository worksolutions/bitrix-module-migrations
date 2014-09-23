<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Reference;


use WS\Migrations\Entities\DbVersionReferencesTable;

class ReferenceController {
    const GROUP_IBLOCK = 'iblock';
    const GROUP_IBLOCK_PROPERTY = 'iblockProperty';
    const GROUP_IBLOCK_SECTION = 'iblockSection';

    private $_currentDbVersion;

    private $_onRegister;

    public function __construct($currentDbVersion) {
        $this->_currentDbVersion = $currentDbVersion;
    }

    /**
     * @param ReferenceItem $item
     * @return $this
     * @throws \Exception
     */
    public function registerItem(ReferenceItem $item) {
        !$item->dbVersion && $item->dbVersion = $this->_currentDbVersion;
        !$item->reference && $item->reference = md5($item->dbVersion.$item->group.$item->id);

        $hasRefByVersion = DbVersionReferencesTable::getList(array(
            'filter' => array(
                '=REFERENCE' => $item->reference,
                '=DB_VERSION' => $item->dbVersion
            )
        ))->fetch();
        $hasRefByVersion && DbVersionReferencesTable::delete($hasRefByVersion['ID']);

        $hasItem = DbVersionReferencesTable::getList(array(
            'filter' => array(
                '=DB_VERSION' => $item->dbVersion,
                '=GROUP' => $item->group,
                '=ITEM_ID' => $item->id
            )
        ))->fetch();
        if ($hasItem) {
            throw new \Exception('Item '.$item->group.' ('.$item->id.') by version '.$item->dbVersion.' been registered before');
        }

        DbVersionReferencesTable::add(array(
            'REFERENCE' => $item->reference,
            'DB_VERSION' => $item->dbVersion,
            'GROUP' => $item->group,
            'ITEM_ID' => $item->id
        ));
        $onRegister = $this->_onRegister;
        $onRegister && $item->dbVersion == $this->_currentDbVersion && $onRegister($item);
        return $this;
    }

    public function removeCurrentVersion($id, $group) {
        $item = $this->getItemById($id, $group);
        if (!$item) {
            return false;
        }
        $res = DbVersionReferencesTable::getList(array(
            'filter' => array(
                '=DB_VERSION' => $this->_currentDbVersion,
                '=GROUP' => $group,
                '=ITEM_ID' => $id
            )
        ))->fetch();
        if (!$res) {
            return false;
        }
        $deleteResult = DbVersionReferencesTable::delete($res['ID']);
        $onRemove = $this->_onRemove;
        $onRemove && $onRemove($item);
        return $deleteResult->isSuccess();
    }

    private function _createItemByDBData(array $data) {
        $item = new ReferenceItem();
        $item->reference = $data['REFERENCE'];
        $item->dbVersion = $data['DB_VERSION'];
        $item->group = $data['GROUP'];
        $item->id = $data['ITEM_ID'];
        return $item;
    }

    /**
     * @param $value
     * @return null|ReferenceItem
     */
    public function getItemCurrentVersionByReference($value) {
        if (!$value) {
            return null;
        }

        $res = DbVersionReferencesTable::getList(array(
            'filter' => array(
                '=REFERENCE' => $value,
                '=DB_VERSION' => $this->_currentDbVersion
            )
        ));
        if (!$data = $res->fetch()) {
            return null;
        }
        return $this->_createItemByDBData($data);
    }

    public function getReferenceValue($id, $group, $dbVersion = null) {
        $item = $this->getItemById($id, $group, $dbVersion);
        if (!$item) {
            throw new \Exception('References item not exists by '.var_export(array('id' => $id, 'group' => $group, 'dbVersion' => $dbVersion), true));
        }
        return $item->reference;
    }

    /**
     * @param $id
     * @param $group
     * @param $dbVersion
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Exception
     * @return null|ReferenceItem
     */
    public function getItemById($id, $group, $dbVersion = null) {
        $res = DbVersionReferencesTable::getList(array(
            'filter' => array(
                '=ITEM_ID' => (int) $id,
                '=DB_VERSION' => $dbVersion ?: $this->_currentDbVersion,
                '=GROUP' => $group
            )
        ));
        if (!$data = $res->fetch()) {
            throw new \Exception('References item not exists by '.var_export(array('id' => $id, 'group' => $group, 'dbVersion' => $dbVersion), true));
        }
        return $this->_createItemByDBData($data);
    }

    /**
     * @param $id
     * @param $group
     * @param $dbVersion
     * @return mixed
     */
    public function getItemId($id, $group, $dbVersion = null) {
        $item = $this->getItemById($id, $group, $dbVersion);
        return $item->id;
    }

    /**
     * @param $id
     * @param $group
     * @param string|null $dbVersion
     * @return bool
     */
    public function hasItemId($id, $group, $dbVersion = null) {
        try {
            $item = $this->getItemById($id, $group, $dbVersion);
            return (bool) $item;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function onRegister($callback) {
        if (!is_callable($callback)) {
            return ;
        }
        $this->_onRegister = $callback;
        return $this;
    }

    public function getCurrentIdByOtherVersion($id, $group, $dbVersion) {
        $reference = $this->getReferenceValue($id, $group, $dbVersion);
        if (!$reference) {
            throw new \Exception('References not exists by '.var_export(array('id' => $id, 'group' => $group, 'dbVersion' => $dbVersion), true));
        }
        $item = $this->getItemCurrentVersionByReference($reference);
        return $item->id;
    }

    public function setupNewVersion($cloneVersion) {
        if (!$cloneVersion) {
            throw new \Exception('Clone version empty');
        }
        $res = DbVersionReferencesTable::getList(array(
            'filter' => array(
                'DB_VERSION' => $this->_currentDbVersion
            )
        ));
        $this->_currentDbVersion = $cloneVersion;
        while ($itemData = $res->fetch()) {
            $item = $this->_createItemByDBData($itemData);
            $item->dbVersion = $cloneVersion;
            $this->registerItem($item);
        }
        return true;
    }

    /**
     * @return ReferenceItem[]
     */
    public function getItems() {
        $dbRes = DbVersionReferencesTable::getList();
        $res = array();
        while ($itemData = $dbRes->fetch()) {
            $res[] = $this->_createItemByDBData($itemData);
        }
        return $res;
    }

    public function deleteAll() {
        $dbRes = DbVersionReferencesTable::getList();
        while ($itemData = $dbRes->fetch()) {
            DbVersionReferencesTable::delete($itemData['ID']);
        }
    }
}
