<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use WS\Migrations\ChangeDataCollector\Collector;
use WS\Migrations\Module;
use WS\Migrations\Tests\AbstractCase;

class UpdateTestCase extends AbstractCase {
    const FIXTURE_TYPE_ADD = 'add_collection';
    const FIXTURE_TYPE_UPDATE = 'update_collection';
    const FIXTURE_TYPE_DELETE = 'delete_collection';

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
        $addedId = array_shift($aAddedId);


        $this->assertNotEmpty($ibCountAfter, 'Запись ИБ должна присутствовать');
        $this->assertNotEquals($ibCountAfter, $ibCountBefore, 'Не добавилась запись инфоблока');
        $this->assertNotEmpty($addedId, 'Недоступен идентификатор нового инфоблока');

        $rsProps = \CIBlockProperty::GetList(null, array('IBLOCK_ID' => $addedId));
        $this->assertNotEmpty($rsProps->AffectedRowsCount(), 'Недоступны добавленные свойства информационного блока');

        $rsSections = \CIBlockSection::getList(null, array('IBLOCK_ID' => $addedId), false, array('ID'));
        $this->assertNotEmpty($rsSections->AffectedRowsCount(), 'Недоступны добавленные секции информационного блока');
    }

    public function testUpdate() {
        return;

        // Проверка/фиксация состояния системы ДО
        /**
         *  - просмотр данных (имена name)
         */
        $this->_applyFixtures(self::FIXTURE_TYPE_UPDATE);

        // Проверка состояния системы ПОСЛЕ
        /**
         *  - сравнение данных имен
         *  - инфоблока
         *  - секции инфоблока
         */
    }

    public function testDelete() {
        return;

        // Проверка/фиксация состояния системы ДО
        /**
         *  - колчичество инфоблоков
         */
        $this->_applyFixtures(self::FIXTURE_TYPE_DELETE);

        // Проверка состояния системы ПОСЛЕ
        /**
         *  - сравнение данных имен
         *  - инфоблока
         *  - секции инфоблока
         */
    }
}