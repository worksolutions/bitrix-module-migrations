<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\IblockType;


use WS\Migrations\ChangeHandler;

class IblockTypeAdd extends ChangeHandler {
    public function getName() {
        return $this->getLocalization()->getDataByPath('iblockTypeAdd.name');
    }

    public function change($data, Catcher $catcher) {
        $catcher->fixChangeData($data[0]);
    }

    public function update(Catcher $catcher) {
    }
}