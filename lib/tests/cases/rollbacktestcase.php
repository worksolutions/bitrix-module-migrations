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

class RollbackTestCase extends AbstractCase {

    public function name() {
        return $this->localization->message('name');
    }

    public function description() {
        return '';
    }

    public function init() {
        \CModule::IncludeModule('iblock');
        Module::getInstance()->clearReferences();
    }

    public function testReinitIblockReference() {
        $beforeApplyFix = array(
            'iblocks' => IblockTable::getList()->getSelectedRowsCount(),
            'properties' => PropertyTable::getList()->getSelectedRowsCount(),
            'sections' => SectionTable::getList()->getSelectedRowsCount(),
        );

        $collector = Collector::createByFile(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'add_collection.json');
        $this->assertNotEmpty($collector->getFixes());
        Module::getInstance()->applyFixesList($collector->getFixes());

        $afterApplyFix = array(
            'iblocks' => IblockTable::getList()->getSelectedRowsCount(),
            'properties' => PropertyTable::getList()->getSelectedRowsCount(),
            'sections' => SectionTable::getList()->getSelectedRowsCount(),
        );

        Module::getInstance()->rollbackLastChanges();
        $afterRollback = array(
            'iblocks' => IblockTable::getList()->getSelectedRowsCount(),
            'properties' => PropertyTable::getList()->getSelectedRowsCount(),
            'sections' => SectionTable::getList()->getSelectedRowsCount(),
        );

        Module::getInstance()->applyFixesList($collector->getFixes());
        $afterRollbackApply = array(
            'iblocks' => IblockTable::getList()->getSelectedRowsCount(),
            'properties' => PropertyTable::getList()->getSelectedRowsCount(),
            'sections' => SectionTable::getList()->getSelectedRowsCount(),
        );

        $this->assertEquals($beforeApplyFix['iblocks'], $afterApplyFix['iblocks'] - 1, $this->errorMessage('iblock not created after apply fix'));
        $this->assertEquals($beforeApplyFix['properties'], $afterApplyFix['properties'] - 2, $this->errorMessage('properties not created after apply fix'));
        $this->assertEquals($beforeApplyFix['sections'], $afterApplyFix['sections'] - 1, $this->errorMessage('sections not created after apply fix'));

        $this->assertEquals($beforeApplyFix['iblocks'], $afterRollback['iblocks'], $this->errorMessage('iblock not removed after rollback fix'));
        $this->assertEquals($beforeApplyFix['properties'], $afterRollback['properties'], $this->errorMessage('properties not removed after rollback fix'));
        $this->assertEquals($beforeApplyFix['sections'], $afterRollback['sections'], $this->errorMessage('sections not removed after rollback fix'));

        $this->assertEquals($afterRollback['iblocks'] + 1, $afterRollbackApply['iblocks'], $this->errorMessage('iblock not created after apply rollback fix'));
        $this->assertEquals($afterRollback['properties'] + 2, $afterRollbackApply['properties'], $this->errorMessage('properties not created after apply rollback fix'));
        $this->assertEquals($afterRollback['sections'] + 1, $afterRollbackApply['sections'], $this->errorMessage('sections not created after apply rollback fix'));
    }
}