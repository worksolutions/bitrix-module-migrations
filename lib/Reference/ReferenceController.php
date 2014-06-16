<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Reference;


use WS\Migrations\Entities\DbVersionReferences;

class ReferenceController {
    private $_currentDbVersion;

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
        DbVersionReferences::add(array(
            'REFERENCE' => $item->reference,
            'DB_VERSION' => $item->dbVersion,
            'GROUP' => $item->group,
            'ITEM_ID' => $item->id
        ));
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

        $res = DbVersionReferences::getList(array(
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
        $res = DbVersionReferences::getList(array(
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

    public function getItemByOtherVersion($dbVersion, $id, $group) {
        return $this->getItem($this->getItemByOtherVersion($dbVersion, $id, $group));
    }
}
