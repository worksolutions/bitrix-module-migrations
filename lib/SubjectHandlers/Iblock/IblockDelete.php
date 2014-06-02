<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\Iblock;


use WS\Migrations\Catcher;
use WS\Migrations\SubjectHandler;

class IblockDelete extends SubjectHandler {
    private $_beforeChangeData;

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        $this->getLocalization()->getDataByPath('iblockDelete.name');
    }

    public function beforeChange($data) {
        $iblockId = $data[0];
        $iblockData = \CIBlock::GetArrayByID($iblockId);
        $this->_beforeChangeData[$iblockId] = $iblockData;
    }

    public function afterChange($data, Catcher $catcher) {
        $iblockId = $data[0];
        $catcher->fixChangeData(array(
            'before' => $this->_beforeChangeData[$iblockId],
            'id' => $iblockId
        ));
    }

    public function update(Catcher $catcher) {
    }

    public function rollback(Catcher $catcher) {
    }


}