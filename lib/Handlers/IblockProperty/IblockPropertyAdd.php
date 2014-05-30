<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\IblockProperty;


use WS\Migrations\Catcher;
use WS\Migrations\ChangeHandler;

class IblockPropertyAdd extends ChangeHandler{

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        return $this->getLocalization()->getDataByPath('iblockPropertyAdd.name');
    }

    public function change($data, Catcher $catcher) {
        $catcher->fixChangeData($data[0]);
    }

    public function update(Catcher $catcher) {
    }

    public function rollback(Catcher $catcher) {
    }
}