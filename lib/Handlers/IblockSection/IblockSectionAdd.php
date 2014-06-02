<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\IblockSection;


use WS\Migrations\ChangeHandler;

class IblockSectionAdd extends ChangeHandler{
    public function getName() {
        return $this->getLocalization()->getDataByPath('iblockSectionAdd.name');
    }

    public function change($data, Catcher $catcher) {
        $catcher->fixChangeData($data[0]);
    }

    public function update(Catcher $catcher) {
    }
}