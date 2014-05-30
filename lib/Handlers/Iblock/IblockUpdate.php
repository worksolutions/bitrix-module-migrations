<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\Iblock;


use WS\Migrations\Catcher;
use WS\Migrations\ChangeHandler;

class IblockUpdate extends ChangeHandler{

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        $this->getLocalization()->getDataByPath('name');
    }

    public function beforeChange($data) {
    }

    public function afterChange($data, Catcher $catcher) {
    }
}