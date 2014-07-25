<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\SectionTable;
use WS\Migrations\ChangeDataCollector\Collector;
use WS\Migrations\Module;
use WS\Migrations\Tests\AbstractCase;

class UpdateTestCase extends AbstractCase {
    const FIXTURE_TYPE_ADD = 'add_collection';
    const FIXTURE_TYPE_UPDATE = 'update_collection';
    const FIXTURE_TYPE_IBLOCK_DELETE = 'delete_iblock';
    const FIXTURE_TYPE_SECTION_DELETE = 'delete_section';
    const FIXTURE_TYPE_PROPERTY_DELETE = 'delete_property';

    private $_processIblockId = null;

    public function name() {
        return 'Обновление изменений';
    }

    public function description() {
        return 'Тестирование обновления изменений согласно фиксациям';
    }

    public function init() {
        \CModule::IncludeModule('iblock');
        Module::getInstance()->clearReferences();
    }

    private function _applyFixtures($type) {
        $collector = Collector::createByFile(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.$type.'.json');
        $this->assertNotEmpty($collector->getFixes());
        Module::getInstance()->applyFixesList($collector->getFixes());
    }

    public function testAdd() {
        /** @var $dbList \CDBResult */
        $dbList = \CIBlock::GetList();
        $ibCountBefore = $dbList->SelectedRowsCount();
        $beforeIds = array();
        while ($arIblock = $dbList->Fetch()) {
            $beforeIds[] = $arIblock['ID'];
        }
        $this->_applyFixtures(self::FIXTURE_TYPE_ADD);

        $dbList = \CIBlock::GetList();
        $ibCountAfter = $dbList->SelectedRowsCount();
        $afterIds = array();
        while ($arIblock = $dbList->Fetch()) {
            $afterIds[] = $arIblock['ID'];
        }

        $aAddedId = array_diff($afterIds, $beforeIds);
        $this->_processIblockId = array_shift($aAddedId);


        $this->assertNotEmpty($ibCountAfter, 'Запись ИБ должна присутствовать');
        $this->assertNotEquals($ibCountAfter, $ibCountBefore, 'Не добавилась запись инфоблока');
        $this->assertNotEmpty($this->_processIblockId, 'Недоступен идентификатор нового инфоблока');

        $rsProps = \CIBlockProperty::GetList(null, array('IBLOCK_ID' => $this->_processIblockId));
        $this->assertNotEmpty($rsProps->AffectedRowsCount(), 'Недоступны добавленные свойства информационного блока');

        $rsSections = \CIBlockSection::getList(null, array('IBLOCK_ID' => $this->_processIblockId), false, array('ID'));
        $this->assertNotEmpty($rsSections->AffectedRowsCount(), 'Недоступны добавленные секции информационного блока');
    }

    public function testUpdate() {
        $arIblock = IblockTable::getList(array(
            'filter' => array(
                '=ID' => $this->_processIblockId
            )
        ))->fetch();
        $this->assertEquals($arIblock['NAME'], 'Added Iblock Test', 'Несоответствует инициализационному имени');
        $this->_applyFixtures(self::FIXTURE_TYPE_UPDATE);
        $arIblock = IblockTable::getList(array(
            'filter' => array(
                '=ID' => $this->_processIblockId
            )
        ))->fetch();
        $this->assertEquals($arIblock['NAME'], 'Added Iblock Test chenge NAME', 'Имя инфоблока не изменилось');

        $sectionData = SectionTable::getList(array(
            'filter' => array(
                '=IBLOCK_ID' => $this->_processIblockId
            )
        ))->fetch();
        $this->assertEquals($sectionData['NAME'], 'Test Section', 'Имя инфоблока не изменилось');
    }

    public function testDelete() {
        $this->_applyFixtures(self::FIXTURE_TYPE_SECTION_DELETE);

        $rsSection = SectionTable::getList(array(
            'filter' => array(
                '=IBLOCK_ID' => $this->_processIblockId
            )
        ));
        $this->assertEmpty($rsSection->getSelectedRowsCount(), 'Секции быть недолжно');

        $this->_applyFixtures(self::FIXTURE_TYPE_PROPERTY_DELETE);
        $rsProps = PropertyTable::getList(array(
            'filter' => array(
                '=IBLOCK_ID' => $this->_processIblockId
            )
        ));
        $this->assertEquals($rsProps->getSelectedRowsCount(), 1, 'У инфоблока остается только одно свойство');

        $dbList = \CIBlock::GetList();
        $ibCountBefore = $dbList->SelectedRowsCount();

        $this->_applyFixtures(self::FIXTURE_TYPE_IBLOCK_DELETE);

        $dbList = \CIBlock::GetList();
        $ibCountAfter = $dbList->SelectedRowsCount();

        $this->assertNotEquals($ibCountBefore, $ibCountAfter, 'Инфоблок небыл удален');

        $arIblock = IblockTable::getList(array(
            'filter' => array(
                '=ID' => $this->_processIblockId
            )
        ))->fetch();

        $this->assertEmpty($arIblock, 'Инфоблок существует');
    }

    public function testCreateNewReferenceFixes() {
        $collector = Module::getInstance()->getDutyCollector();
        $fixes = $collector->getFixes();
        $this->assertNotEmpty($fixes, 'Необходимо наличие фиксаций добавления ссылок');
        foreach ($fixes as $fix) {
            if ($fix->getProcess() != 'reference') {
                $this->throwError('При обновлении регистрируются только ссылки');
            }
        }
    }
}