<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\Iblock;


use WS\Migrations\Catcher;
use WS\Migrations\SubjectHandler;

class IblockAdd extends SubjectHandler {

    public function getName() {
        return $this->getLocalization()->getDataByPath('iblockAdd.name');
    }

    public function change($data, Catcher $catcher) {
        $catcher->fixChangeData($data[0]);
    }

    public function update(Catcher $catcher) {
    }
}