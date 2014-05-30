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
        $this->_beforeChange = $data;
    }

    public function afterChange($data, Catcher $catcher) {
        $catcher->fixChangeData(array(
            'before' => $this->_beforeChange,
            'after' => $data
        ));
    }

    public function update(Catcher $catcher) {
    }

    public function rollback(Catcher $catcher) {
    }
}