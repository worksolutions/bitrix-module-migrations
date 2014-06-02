<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\IblockType;


use WS\Migrations\ChangeHandler;

class IblockTypeDelete extends ChangeHandler {
    private $_beforeChangeData;

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        $this->getLocalization()->getDataByPath('iblockTypeDelete.name');
    }

    public function beforeChange($data) {
        $id = $data[0]['ID'];
        $data = \CIBlockType::GetByID($id)->Fetch();
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