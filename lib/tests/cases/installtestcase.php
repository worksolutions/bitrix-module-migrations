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
        return 'Тестирование процедуры установки';
    }

    public function description() {
        return '';
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
        $this->assertEquals($dbRsIblock->getSelectedRowsCount(), $dbRsRef->getSelectedRowsCount(), 'Количество ссылок по инфоблокам и записей инфоблоков должно совпадать');

        $dbRsRef = DbVersionReferencesTable::getList(array(
            'filter' => array(
                'GROUP' => ReferenceController::GROUP_IBLOCK_PROPERTY
            )
        ));
        $dbRsProp = PropertyTable::getList();
        $this->assertEquals($dbRsProp->getSelectedRowsCount(), $dbRsRef->getSelectedRowsCount(), 'Количество ссылок по свойствам инфоблоков и записей должно совпадать');

        $dbRsRef = DbVersionReferencesTable::getList(array(
            'filter' => array(
                'GROUP' => ReferenceController::GROUP_IBLOCK_SECTION
            )
        ));
        $dbRsSection = SectionTable::getList();
        $this->assertEquals($dbRsSection->getSelectedRowsCount(), $dbRsRef->getSelectedRowsCount(), 'Количество ссылок по разделам инфоблоков и записей должно совпадать');
    }

}