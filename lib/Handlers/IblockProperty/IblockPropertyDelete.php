<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\IblockProperty;


use WS\Migrations\Catcher;
use WS\Migrations\ChangeHandler;

class IblockPropertyDelete extends ChangeHandler {

    private $_beforeChangeData = array();

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        return $this->getLocalization()->getDataByPath('iblockPropertyDelete.name');
    }

    public function beforeChange($data) {
        $this->_beforeChangeData = $data;
    }

    public function afterChange($data, Catcher $catcher) {
        $catcher->fixChangeData(array(
            'before' => $this->_beforeChangeData,
            'after' => $data
        ));
    }

    public function update(Catcher $catcher) {
    }

    public function rollback(Catcher $catcher) {
    }
}