<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\Iblock;


use WS\Migrations\Catcher;
use WS\Migrations\SubjectHandler;

class IblockUpdate extends SubjectHandler{
    private $_beforeChangeData = array();

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        $this->getLocalization()->getDataByPath('iblockUpdate.name');
    }

    public function beforeChange($data) {
        $iblockId = $data[0]['ID'];
        $data = \CIBlock::GetArrayByID($iblockId);
        $this->_beforeChangeData[$iblockId] = $data;
    }

    public function afterChange($data, Catcher $catcher) {
        $iblockId = $data[0]['ID'];
        $catcher->fixChangeData(array(
            'before' => $this->_beforeChangeData[$iblockId],
            'after' => $data[0]
        ));
    }
}