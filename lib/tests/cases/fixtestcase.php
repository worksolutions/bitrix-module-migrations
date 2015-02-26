<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\SectionTable;
use WS\Migrations\ChangeDataCollector\Collector;
use WS\Migrations\Entities\AppliedChangesLogModel;
use WS\Migrations\Module;
use WS\Migrations\Processes\AddProcess;
use WS\Migrations\Processes\DeleteProcess;
use WS\Migrations\Processes\UpdateProcess;
use WS\Migrations\SubjectHandlers\IblockHandler;
use WS\Migrations\SubjectHandlers\IblockPropertyHandler;
use WS\Migrations\SubjectHandlers\IblockSectionHandler;
use WS\Migrations\Tests\AbstractCase;

class FixTestCase extends AbstractCase {

    /**
     * @var Collector
     */
    private $_currentDutyCollector;

    const VERSION = 'test';

    const OWNER_NAME = 'Owner Changes';

    private $_iblockId, $_propertyId, $_sectionId;

    public function name() {
        return $this->localization->message('name');
    }

    public function description() {
        return $this->localization->message('description');
    }

    public function init() {
        \CModule::IncludeModule('iblock');
        Module::getInstance()->clearReferences();
        $applyLogs = AppliedChangesLogModel::find();
        foreach ($applyLogs as $log) {
            $log->delete();
        }
    }

    /**
     * @param $process
     * @param null $subject
     * @return array Список массивов данных
     * @throws \Exception
     */
    private function _getCollectorFixes($process, $subject = null) {
        if (!$this->_currentDutyCollector) {
            throw new \Exception('Duty collector not exists');
        }
        $fixes = $this->_currentDutyCollector->getFixesData(self::VERSION, self::OWNER_NAME);
        $res = array();
        foreach ($fixes as $fixData) {
            $fixData['process'] == $process
                &&
            ($subject && $fixData['subject'] == $subject || !$subject)
                &&
            $res[] = $fixData;
        }
        return $res;
    }

    private function _injectDutyCollector() {
        $collector = Collector::createInstance(__DIR__);
        $collector->notStored();
        Module::getInstance()->injectDutyCollector($collector);
        $this->_currentDutyCollector = $collector;
        return $collector;
    }

    public function testAdd() {
        $this->_injectDutyCollector();
        $ibType = \CIBlockType::GetList()->Fetch();
        $ib = new \CIBlock;

        $ibId = $ib->Add(array(
            'IBLOCK_TYPE_ID' => $ibType['ID'],
            'NAME' => 'New Iblock',
            'SITE_ID' => 's1'
        ));

        $this->assertNotEmpty($ibId, $this->errorMessage('not create iblock id', array(
            ':lastError' => $ib->LAST_ERROR
        )));

        $prop = new \CIBlockProperty();
        $propId = $prop->Add(array(
            'IBLOCK_ID' => $ibId,
            'CODE' => 'propCode',
            'NAME' => 'Property NAME'
        ));

        $this->assertNotEmpty($propId, $this->errorMessage('not create property iblock id', array(
            ':lastError' => $ib->LAST_ERROR
        )));

        $sec = new \CIBlockSection();
        $secId = $sec->Add(array(
            'IBLOCK_ID' => $ibId,
            'NAME' => 'Iblock Section'
        ));

        $this->assertNotEmpty($secId, $this->errorMessage('not create section iblock id', array(
            ':lastError' => $ib->LAST_ERROR
        )));

        // В итоге должны получится

        // данные по добавлению ИБ
        $this->assertNotEmpty($this->_getCollectorFixes(AddProcess::className(), IblockHandler::className()), 'Iblock is not added');
        // данные по добавлению свойства
        $this->assertNotEmpty($this->_getCollectorFixes(AddProcess::className(), IblockPropertyHandler::className()), 'Iblock property is not added');
        // данные по добавлению секции
        $this->assertNotEmpty($this->_getCollectorFixes(AddProcess::className(), IblockSectionHandler::className()), 'Section is not added');

        $refFixes = $this->_getCollectorFixes('reference');
        // фиксация изменений
        Module::getInstance()->commitDutyChanges();
        // добавлены записи журнала обновлений (в базу)
        /** @var $logRecords AppliedChangesLogModel[] */
        $logRecords = AppliedChangesLogModel::find(array(
            'order' => array(
                'id' => 'desc'
            ),
            'limit' => 10
        ));

        $this->assertTrue(count($logRecords) > 3);
        $iterationsCount = 0;
        foreach ($logRecords as $logRecord) {
            if ($logRecord->processName == Module::SPECIAL_PROCESS_FIX_REFERENCE) {
                continue;
            }
            if ($logRecord->processName != AddProcess::className()) {
                $this->throwError($this->errorMessage('last log records need been update process'), $logRecord->processName);
            }
            if (++$iterationsCount > 3) {
                break;
            }
            $data = $logRecord->updateData;
            switch ($logRecord->subjectName) {
                case IblockHandler::className():
                    (!$data['iblock'] || ($data['iblock']['ID'] != $ibId))
                    &&
                    $this->throwError($this->errorMessage('iblock not registered after update', array(
                        ':actual' => $data['iblock']['ID'],
                        ':need' => $ibId
                    )));
                    break;
                case IblockPropertyHandler::className():
                    ($data['ID'] != $propId)
                    &&
                    $this->throwError($this->errorMessage('property iblock not registered after update', array(
                        ':original' => $propId,
                        ':actual' => $data['ID']
                    )));
                    break;
                case IblockSectionHandler::className():
                    $data['ID'] != $secId
                    &&
                    $this->throwError($this->errorMessage('section iblock not registered after update', array(
                        ':original' => $secId,
                        ':actual' => $data['ID']
                    )));
                    break;
            }
        }

        // добавлены три вида ссылок в фиксациях
        $this->assertEquals(3, count($refFixes), $this->errorMessage('links expected count', array(':count' => 3)));

        $this->_iblockId = $ibId;
        $this->_propertyId = $propId;
        $this->_sectionId = $secId;
    }

    /**
     * Зависимость от добавления
     * @after testAdd
     */
    public function testUpdate() {
        $this->_injectDutyCollector();
        $this->assertNotEmpty($this->_iblockId);
        $this->assertNotEmpty($this->_propertyId);
        $this->assertNotEmpty($this->_sectionId);

        $arIblock = \CIBlock::GetArrayByID($this->_iblockId);
        $arIblock['NAME'] .= '2';
        $name = $arIblock['NAME'];

        $iblock = new \CIBlock();
        $updateResult = $iblock->Update($this->_iblockId, $arIblock);

        $this->assertTrue($updateResult, $this->errorMessage('error update result'));
        // для начала определяется просто как снимок
        $fixes = $this->_getCollectorFixes(UpdateProcess::className());
        $this->assertEquals(count($fixes), 1, $this->errorMessage('having one fixing updates'));
        $this->assertEquals($fixes[0]['data']['iblock']['NAME'], $name, $this->errorMessage('fixing name change'));

        // фиксация изменений
        Module::getInstance()->commitDutyChanges();
    }

    /**
     * Зависимость от добавления
     * @after testUpdate
     */
    public function testDelete() {
        $this->_injectDutyCollector();
        $deleteResult = \CIBlock::Delete($this->_iblockId);

        $this->assertTrue($deleteResult, $this->errorMessage('iblock must be removed from the database'));

        $this->assertCount($this->_getCollectorFixes(DeleteProcess::className()), 3, $this->errorMessage('uninstall entries must be: section, property information, iblock'));
        $this->assertCount($sectionFixesList = $this->_getCollectorFixes(DeleteProcess::className(), IblockSectionHandler::className()), 1, $this->errorMessage('should be uninstall entries: Section'));
        $this->assertCount($propsFixesList = $this->_getCollectorFixes(DeleteProcess::className(), IblockPropertyHandler::className()), 1, $this->errorMessage('should be uninstall entries: Property'));
        $this->assertCount($iblockFixesList = $this->_getCollectorFixes(DeleteProcess::className(), IblockHandler::className()), 1, $this->errorMessage('should be uninstall entries: Iblock'));

        $sectionFixData = array_shift($sectionFixesList);
        $this->assertTrue(is_scalar($sectionFixData['data']), $this->errorMessage('data pack when you remove the section must be an identifier', array(
            ':value' => self::exportValue($sectionFixData['data'])
        )));

        $propFixData = array_shift($propsFixesList);
        $this->assertTrue(is_scalar($propFixData['data']), $this->errorMessage('data pack when you remove the property must be an identifier', array(
            ':value' => self::exportValue($propFixData['data'])
        )));

        $iblockFixData = array_shift($iblockFixesList);
        $this->assertTrue(is_scalar($iblockFixData['data']), $this->errorMessage('data pack when you remove the iblock must be an identifier', array(
            ':value' => self::exportValue($iblockFixData['data'])
        )));
        $this->assertNotEmpty($iblockFixData['originalData'], $this->errorMessage('data should be stored remotely information block'));

        // фиксация изменений
        Module::getInstance()->commitDutyChanges();
    }

    /**
     * @after testDelete
     */
    public function testRollbackDelete() {
        /** @var $list AppliedChangesLogModel[] */
        $list = AppliedChangesLogModel::find(array(
            'limit' => 3,
            'order' => array('id' => 'DESC')
        ));
        $this->assertCount($list, 3, $this->errorMessage('should be in an amount of writable', array(':count' => 3)));

        foreach ($list as $lItem) {
            $this->assertTrue($lItem->processName == DeleteProcess::className(), $this->errorMessage('logging process should be - Disposal'));
        }
        $rsIblock = \CIBlock::getList();
        $countIbBefore = $rsIblock->SelectedRowsCount();
        $iblocksBefore = array();
        while ($arIb = $rsIblock->Fetch()) {
            $iblocksBefore[] = $arIb['ID'];
        }
        Module::getInstance()->rollbackByLogs($list ?: array());
        $rsIblock = \CIBlock::getList();
        $countIbAfter = $rsIblock->SelectedRowsCount();
        $iblocksAfter = array();
        while ($arIb = $rsIblock->Fetch()) {
            $iblocksAfter[] = $arIb['ID'];
        }
        $rebuildIblockId = array_diff($iblocksAfter, $iblocksBefore);
        $rebuildIblockId = array_shift($rebuildIblockId);

        $this->assertEquals($countIbAfter, $countIbBefore + 1, $this->errorMessage('information block data to be restored'));
        $this->assertEquals($rebuildIblockId, $this->_iblockId, $this->errorMessage('iblock restored identifier changed'));

        $rsProp = PropertyTable::getList(array(
            'filter' => array(
                '=IBLOCK_ID' => $rebuildIblockId
            )
        ));
        $this->assertTrue($rsProp->getSelectedRowsCount() > 0, $this->errorMessage('must present properties of reduced information iblock'), array(':iblockId' => $rebuildIblockId));

        $rsSections = SectionTable::getList(array(
            'filter' => array(
                '=IBLOCK_ID' => $rebuildIblockId
            )
        ));
        $this->assertTrue($rsSections->getSelectedRowsCount() > 0, $this->errorMessage('must present sections of reduced information iblock', array(':iblockId' => $rebuildIblockId)));
    }
}