<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests\Cases;


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

    const VERSION = 'testVersion';

    private $_iblockId, $_propertyId, $_sectionId;

    public function name() {
        return 'Тестирование фиксаций изменений';
    }

    public function description() {
        return 'Проверка фиксации изменений при изменении структуры предметной области';
    }

    public function setUp() {
        \CModule::IncludeModule('iblock');
    }

    private function _getCollectorFixes($process, $subject = null) {
        if (!$this->_currentDutyCollector) {
            throw new \Exception('Duty collector not exists');
        }
        $fixes = $this->_currentDutyCollector->getFixesData(self::VERSION);
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

        $this->assertNotEmpty($ibId, 'Не создан идентификатор инфоблока.'.$ib->LAST_ERROR);

        $prop = new \CIBlockProperty();
        $propId = $prop->Add(array(
            'IBLOCK_ID' => $ibId,
            'CODE' => 'propCode',
            'NAME' => 'Property NAME'
        ));
        $this->assertNotEmpty($propId, 'Не создано свойство инфоблока.'.$prop->LAST_ERROR);

        $sec = new \CIBlockSection();
        $secId = $sec->Add(array(
            'IBLOCK_ID' => $ibId,
            'NAME' => 'Iblock Section'
        ));
        $this->assertNotEmpty($secId, 'Не создана секция инфоблока.'.$sec->LAST_ERROR);

        // В итоге должны получится

        // данные по добавлению ИБ
        $this->assertNotEmpty($this->_getCollectorFixes(AddProcess::className(), IblockHandler::className()));
        // данные по добавлению свойства
        $this->assertNotEmpty($this->_getCollectorFixes(AddProcess::className(), IblockPropertyHandler::className()));
        // данные по добавлению секции
        $this->assertNotEmpty($this->_getCollectorFixes(AddProcess::className(), IblockSectionHandler::className()));

        $refFixes = $this->_getCollectorFixes('reference');
        // фиксация изменений
        Module::getInstance()->commitDutyChanges();
        // добавлены записи журнала обновлений (в базу)
        /** @var $logRecords AppliedChangesLogModel[] */
        $logRecords = AppliedChangesLogModel::find(array(
            'order' => array(
                'id' => 'desc'
            ),
            'limit' => 3
        ));

        $this->assertEquals(3, count($logRecords));
        foreach ($logRecords as $logRecord) {
            if ($logRecord->processName != AddProcess::className()) {
                $this->throwError('Последними записями лога должен быть процесс добавления');
            }
            $data = $logRecord->updateData;
            switch ($logRecord->subjectName) {
                case IblockHandler::className():
                    (!$data['iblock'] || ($data['iblock']['ID'] != $ibId)) && $this->throwError('Инфоблок незарегистрирован в обновлении, тут '.$data['iblock']['ID'].', нужен '.$ibId);
                    break;
                case IblockPropertyHandler::className():
                    ($data['ID'] != $propId) && $this->throwError('Свойство незарегистрировано в обновлении, оригинал - '.$propId.' получено '.$data['ID']);
                    break;
                case IblockSectionHandler::className():
                    $data['ID'] != $secId && $this->throwError('Секция незарегистрирована в обновлении, оригинал - '.$secId.' получено '.$data['ID']);
                    break;
            }
        }

        // добавлены три вида ссылок в фиксациях
        $this->assertEquals(3, count($refFixes), 'Ссылока должно быть 3');

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

        $this->assertTrue($updateResult, 'Результат обновления отрицательный');
        // для начала определяется просто как снимок
        $fixes = $this->_getCollectorFixes(UpdateProcess::className());
        $this->assertEquals(count($fixes), 1, 'Наличие одной фиксации обновления');
        $this->assertEquals($fixes[0]['data']['iblock']['NAME'], $name, 'Фиксация на изменение имени');

        $this->assertNotEmpty($this->_getCollectorFixes('reference'), 'При обновлении должны быть ссылочгые данные');
    }

    /**
     * Зависимость от добавления
     * @after testUpdate
     */
    public function testDelete() {
        $this->_injectDutyCollector();
        $iblock = new \CIBlock();
        $deleteResult = $iblock->Delete($this->_iblockId);
        $this->assertTrue($deleteResult, 'Инфоблок должен быть удален из БД');

        $this->assertCount($this->_getCollectorFixes(DeleteProcess::className()), 3, 'Должны быть записи удалений: секция, свойство, инфоблок');
        $this->assertCount($this->_getCollectorFixes(DeleteProcess::className(), IblockSectionHandler::className()), 1, 'Должны быть записи удалений: секция');
        $this->assertNotEmpty($this->_getCollectorFixes('reference'), 'При обновлении должны быть ссылочгые данные');
    }
}