<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\SectionTable;
use WS\Migrations\ChangeDataCollector\Collector;
use WS\Migrations\Entities\DbVersionReferencesTable;
use WS\Migrations\Module;
use WS\Migrations\Reference\ReferenceController;
use WS\Migrations\Tests\AbstractCase;

class UpdateTestCase extends AbstractCase {
    const FIXTURE_TYPE_ADD = 'add_collection';
    const FIXTURE_TYPE_UPDATE = 'update_collection';
    const FIXTURE_TYPE_IBLOCK_DELETE = 'delete_iblock';
    const FIXTURE_TYPE_SECTION_DELETE = 'delete_section';
    const FIXTURE_TYPE_PROPERTY_DELETE = 'delete_property';

    private $_processIblockId = null;

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

    private function _applyFixtures($type) {
        $collector = Collector::createByFile(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.$type.'.json');
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


        $this->assertNotEmpty($ibCountAfter, $this->errorMessage('record IB must be present'));
        $this->assertNotEquals($ibCountAfter, $ibCountBefore, $this->errorMessage('not also recording information block'));
        $this->assertNotEmpty($this->_processIblockId, $this->errorMessage('unavailable identifier of the new information block'));

        $rsProps = \CIBlockProperty::GetList(null, array('IBLOCK_ID' => $this->_processIblockId));
        $this->assertNotEmpty($rsProps->AffectedRowsCount(), $this->errorMessage('added properties not available information block', array(
            ':iblockId' => $this->_processIblockId
        )));

        $rsSections = \CIBlockSection::getList(null, array('IBLOCK_ID' => $this->_processIblockId), false, array('ID'));
        $this->assertNotEmpty($rsSections->AffectedRowsCount(), $this->errorMessage('added sections not available information block'));

        $registerRef = (bool)DbVersionReferencesTable::getList(array(
            'filter' => array(
                '=DB_VERSION' => Module::getInstance()->getDbVersion(),
                '=GROUP' => ReferenceController::GROUP_IBLOCK,
                '=ITEM_ID' => $this->_processIblockId
            )
        ))->fetch();
        $this->assertTrue($registerRef, $this->errorMessage('In added apply not created iblock reference '. $this->_processIblockId));
        $ownerVersions = Module::getInstance()->getOptions()->getOtherVersions();
        $this->assertTrue(in_array('Василий Сазонов', $ownerVersions), $this->errorMessage("Not registered version as `Василий сазонов`"));
    }

    public function testUpdate() {
        $arIblock = IblockTable::getList(array(
            'filter' => array(
                '=ID' => $this->_processIblockId
            )
        ))->fetch();
        $this->assertEquals($arIblock['NAME'], 'Added Iblock Test', $this->errorMessage('inconsistencies initialization name'));
        $this->_applyFixtures(self::FIXTURE_TYPE_UPDATE);
        $arIblock = IblockTable::getList(array(
            'filter' => array(
                '=ID' => $this->_processIblockId
            )
        ))->fetch();
        $this->assertEquals($arIblock['NAME'], 'Added Iblock Test chenge NAME', $this->errorMessage('Name information block has not changed'));

        $sectionData = SectionTable::getList(array(
            'filter' => array(
                '=IBLOCK_ID' => $this->_processIblockId
            )
        ))->fetch();
        $this->assertEquals($sectionData['NAME'], 'Test Section', $this->errorMessage('Name information block has not changed'));
    }

    public function testDelete() {
        $this->_applyFixtures(self::FIXTURE_TYPE_SECTION_DELETE);

        $rsSection = SectionTable::getList(array(
            'filter' => array(
                '=IBLOCK_ID' => $this->_processIblockId
            )
        ));
        $this->assertEmpty($rsSection->getSelectedRowsCount(), $this->errorMessage('section should not be'));

        $this->_applyFixtures(self::FIXTURE_TYPE_PROPERTY_DELETE);
        $rsProps = PropertyTable::getList(array(
            'filter' => array(
                '=IBLOCK_ID' => $this->_processIblockId
            )
        ));
        $this->assertEquals($rsProps->getSelectedRowsCount(), 1, $this->errorMessage('in the information block is only one property'));

        $dbList = \CIBlock::GetList();
        $ibCountBefore = $dbList->SelectedRowsCount();

        $this->_applyFixtures(self::FIXTURE_TYPE_IBLOCK_DELETE);

        $dbList = \CIBlock::GetList();
        $ibCountAfter = $dbList->SelectedRowsCount();

        $this->assertNotEquals($ibCountBefore, $ibCountAfter, $this->errorMessage('iblock not been deleted'));

        $arIblock = IblockTable::getList(array(
            'filter' => array(
                '=ID' => $this->_processIblockId
            )
        ))->fetch();

        $this->assertEmpty($arIblock, $this->errorMessage('iblock exists'));
    }

    public function testCreateNewReferenceFixes() {
        $collector = Module::getInstance()->getDutyCollector();
        $fixes = $collector->getFixes();
        $this->assertNotEmpty($fixes, $this->errorMessage('requires fixations adding links'));
        foreach ($fixes as $fix) {
            if ($fix->getProcess() != 'reference') {
                $this->throwError($this->errorMessage('when upgrading recorded only links'));
            }
        }
    }
}