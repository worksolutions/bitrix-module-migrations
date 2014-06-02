<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\IblockSection;


use WS\Migrations\ChangeHandler;

class IblockSectionUpdate extends ChangeHandler {
    private $_beforeChangeData = array();

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        $this->getLocalization()->getDataByPath('iblockUpdate.name');
    }

    public function beforeChange($data) {
        $id = $data[0]['ID'];
        $data = \CIBlockSection::GetByID($id)->Fetch();
        $this->_beforeChangeData[$id] = $data;
    }

    public function afterChange($data, Catcher $catcher) {
        $id = $data[0]['ID'];
        $catcher->fixChangeData(array(
            'before' => $this->_beforeChangeData[$id],
            'after' => $data[0]
        ));
    }

} 