<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\SectionTable;
use WS\Migrations\Entities\DbVersionReferencesTable;
use WS\Migrations\Module;
use WS\Migrations\Reference\ReferenceController;
use WS\Migrations\Tests\AbstractCase;

class InstallTestCase extends AbstractCase {

    public function name() {
        return $this->localization->message('name');
    }

    public function description() {
        return $this->localization->message('description');
    }

    public function init() {
        \CModule::IncludeModule('iblock');
        Module::getInstance()->clearReferences();
    }


    public function testExistsReferencesRegister() {
        Module::getInstance()->install();

        $dbRsRef = DbVersionReferencesTable::getList(array(
            'filter' => array(
                'GROUP' => ReferenceController::GROUP_IBLOCK
            )
        ));
        $dbRsIblock = IblockTable::getList();
        $this->assertEquals($dbRsIblock->getSelectedRowsCount(), $dbRsRef->getSelectedRowsCount(), $this->errorMessage('number of links to the information block and the information block entries must match'));

        $dbRsRef = DbVersionReferencesTable::getList(array(
            'filter' => array(
                'GROUP' => ReferenceController::GROUP_IBLOCK_PROPERTY
            )
        ));
        $dbRsProp = PropertyTable::getList();
        $this->assertEquals($dbRsProp->getSelectedRowsCount(), $dbRsRef->getSelectedRowsCount(), $this->errorMessage('number of links on the properties of information blocks and records must match'));

        $dbRsRef = DbVersionReferencesTable::getList(array(
            'filter' => array(
                'GROUP' => ReferenceController::GROUP_IBLOCK_SECTION
            )
        ));
        $dbRsSection = SectionTable::getList();
        $this->assertEquals($dbRsSection->getSelectedRowsCount(), $dbRsRef->getSelectedRowsCount(), $this->errorMessage('number of links to information block sections and records must match'));
    }

}