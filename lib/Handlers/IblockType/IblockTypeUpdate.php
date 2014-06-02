<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\IblockType;


use WS\Migrations\ChangeHandler;

class IblockTypeUpdate extends ChangeHandler {
    private $_beforeChangeData = array();

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        $this->getLocalization()->getDataByPath('iblockTypeUpdate.name');
    }

    public function beforeChange($data) {
        $id = $data[0]['ID'];
        $data = \CIBlockType::GetByID($id)->Fetch();
        $this->_beforeChangeData[$id] = $data;
    }

    public function afterChange($data, Catcher $catcher) {
        $id = $data[0]['ID'];
        $catcher->fixChangeData(array(
            'before' => $this->_beforeChangeData[$id],
            'after' => $data[0]
        ));
    }
} 