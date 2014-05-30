<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\IblockProperty;


use WS\Migrations\Catcher;
use WS\Migrations\ChangeHandler;

class IblockPropertyUpdate extends ChangeHandler{
    private $_beforeChange = array();

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        return $this->getLocalization()->getDataByPath('iblockPropertyUpdate.name');
    }

    public function beforeChange($data) {
        $propertyId = $data[0]['ID'];
        $propResult = \CIBlockProperty::GetByID($propertyId);
        if ($data = $propResult->Fetch()) {
            $this->_beforeChange[$propertyId] = $data;
        }
    }

    public function afterChange($data, Catcher $catcher) {
        $propertyId = $data[0]['ID'];
        $catcher->fixChangeData(array(
            'before' => $this->_beforeChange[$propertyId],
            'after' => $data[0]
        ));
    }

    public function update(Catcher $catcher) {
    }

    public function rollback(Catcher $catcher) {
    }
}