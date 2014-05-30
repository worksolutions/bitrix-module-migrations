<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\Iblock;


use WS\Migrations\Catcher;
use WS\Migrations\ChangeHandler;

class IblockAdd extends ChangeHandler {

    public function getName() {
        return $this->getLocalization()->getDataByPath('iblockAdd.name');
    }

    public function change($data, Catcher $catcher) {
        $catcher->fixChangeData($data[0]);
    }

    public function update(Catcher $catcher) {
    }
}