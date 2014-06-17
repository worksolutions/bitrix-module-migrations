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
        DbVersionReferencesTable::add(array(
            'REFERENCE' => $item->reference,
            'DB_VERSION' => $item->dbVersion,
            'GROUP' => $item->group,
            'ITEM_ID' => $item->id
        ));

        $onRegister = $this->_onRegister;
        $onRegister && $onRegister($item);
        return $this;
    }

    /**
     * @param $referenceValue
     * @return null|ReferenceItem
     */
    public function getItem($referenceValue) {
        if (!$referenceValue) {
            return null;
        }

        $res = DbVersionReferencesTable::getList(array(
            'filter' => array(
                '=REFERENCE' => $referenceValue,
                '=DB_VERSION' => $this->_currentDbVersion
            )
        ));
        if (!$data = $res->fetch()) {
            return null;
        }
        $item = new ReferenceItem();
        $item->reference = $data['REFERENCE'];
        $item->dbVersion = $data['DB_VERSION'];
        $item->group = $data['GROUP'];
        $item->id = $data['ITEM_ID'];

        return $item;
    }

    public function getReferenceValueByOtherVersion($dbVersion, $id, $group) {
        $res = DbVersionReferencesTable::getList(array(
            'filter' => array(
                '=ITEM_ID' => $id,
                '=DB_VERSION' => $dbVersion,
                '=GROUP' => $group
            )
        ));
        if (!$data = $res->fetch()) {
            return null;
        }
        return $data['REFERENCE'];
    }

    /**
     * @param $dbVersion
     * @param $id
     * @param $group
     * @return null|ReferenceItem
     */
    public function getItemByOtherVersion($dbVersion, $id, $group) {
        return $this->getItem($this->getItemByOtherVersion($dbVersion, $id, $group));
    }

    /**
     * @param $dbVersion
     * @param $id
     * @param $group
     * @return mixed
     */
    public function getItemIdByOtherVersion($dbVersion, $id, $group) {
        $item = $this->getItemByOtherVersion($dbVersion, $id, $group);
        return $item->id;
    }

    public function onRegister($callback) {
        if (!is_callable($callback)) {
            return ;
        }
        $this->_onRegister = $callback;
        return $this;
    }
}
