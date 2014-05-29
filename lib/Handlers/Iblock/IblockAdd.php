<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\Iblock;


use WS\Migrations\ChangeHandler;

class IblockAdd extends ChangeHandler {

    public function getName() {
        return $this->getLocalization()->getDataByPath('iblockAdd.name');
    }
}