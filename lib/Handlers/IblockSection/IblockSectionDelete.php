<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\IblockSection;


use WS\Migrations\ChangeHandler;

class IblockSectionDelete extends ChangeHandler {
    private $_beforeChangeData;

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        $this->getLocalization()->getDataByPath('iblockDelete.name');
    }

    public function beforeChange($data) {
        $id = $data[0];
        $data = \CIBlockSection::GetByID($id)->Fetch();
        $this->_beforeChangeData[$id] = $data;
    }

    public function afterChange($data, Catcher $catcher) {
        $id = $data[0];
        $catcher->fixChangeData(array(
            'before' => $this->_beforeChangeData[$id],
            'id' => $id
        ));
    }

    public function update(Catcher $catcher) {
    }

    public function rollback(Catcher $catcher) {
    }
}