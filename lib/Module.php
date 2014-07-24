<?php

namespace WS\Migrations;
use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Json;
use WS\Migrations\ChangeDataCollector\Collector;
use WS\Migrations\ChangeDataCollector\CollectorFix;
use WS\Migrations\Entities\AppliedChangesLogModel;
use WS\Migrations\Entities\AppliedChangesLogTable;
use WS\Migrations\Entities\SetupLogModel;
use WS\Migrations\Processes\AddProcess;
use WS\Migrations\Processes\BaseProcess;
use WS\Migrations\Processes\DeleteProcess;
use WS\Migrations\Processes\UpdateProcess;
use WS\Migrations\Reference\ReferenceController;
use WS\Migrations\Reference\ReferenceItem;
use WS\Migrations\SubjectHandlers\BaseSubjectHandler;
use WS\Migrations\SubjectHandlers\IblockHandler;
use WS\Migrations\SubjectHandlers\IblockPropertyHandler;
use WS\Migrations\SubjectHandlers\IblockSectionHandler;
use WS\Migrations\Tests\Starter;

/**
 * Class Module (Singleton)
 *
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */
class Module {

    const FIX_CHANGES_ADD_KEY = 'add';
    const FIX_CHANGES_BEFORE_CHANGE_KEY = 'beforeChange';
    const FIX_CHANGES_AFTER_CHANGE_KEY = 'afterChange';
    const FIX_CHANGES_BEFORE_DELETE_KEY = 'beforeDelete';
    const FIX_CHANGES_AFTER_DELETE_KEY = 'afterDelete';

    const SPECIAL_PROCESS_FIX_REFERENCE = 'reference';
    const SPECIAL_PROCESS_FULL_MIGRATE = 'fullMigrate';

    private $localizePath = null,
            $localizations = array();

    private static $name = 'ws.migrations';

    private $_handlers = array();

    /**
     * @var Collector
     */
    private $_dutyCollector = null;

    /**
     * @var ReferenceController
     */
    private $_referenceController = null;

    private $_processAdd;
    private $_processUpdate;
    private $_processDelete;

    private $_listenMode = true;

    /**
     * @return $this
     */
    private function _enableListen() {
        $this->_listenMode = true;
        return $this;
    }

    /**
     * @return $this
     */
    private function _disableListen() {
        $this->_listenMode = false;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasListen() {
        return (bool)$this->_listenMode;
    }

    private function __construct() {
        $this->localizePath = __DIR__.'/../lang/'.LANGUAGE_ID;
    }

    static public function getName($stripDots = false) {
        $res = static::$name;
        if ($stripDots) {
            $res = str_replace('.', '_', $res);
        }
        return $res;
    }

    /**
     * @return ModuleOptions
     */
    static public function getOptions() {
        return ModuleOptions::getInstance();
    }

    /**
     * @return Module
     */
    static public function getInstance() {
        static $self = null;
        if (!$self) {
            $self = new self;
        }
        return $self;
    }

    static public function listen(){
        EventManager::getInstance()
            ->addEventHandler('main', 'OnBeforeLocalRedirect', array(get_called_class(), 'commitDutyChanges'));
        EventManager::getInstance()
            ->addEventHandler('main', 'OnCheckListGet', array(Starter::className(), 'items'));
        $self = self::getInstance();
        $bxEventManager = EventManager::getInstance();
        foreach ($self->handlers() as $class => $events) {
            foreach ($events as $eventKey => $eventData) {
                $bxEventManager->addEventHandler($eventData[0], $eventData[1], new Callback(function () {
                    $params = func_get_args();
                    $module = array_shift($params);
                    $handlerClass = array_shift($params);
                    $eventKey = array_shift($params);

                    /** @var $module Module */
                    $module->handle($handlerClass, $eventKey, $params);

                }, $self, $class, $eventKey));
            }
        }
        $self->_referenceController = new ReferenceController($self->getDbVersion());
        $fixRefProcess = self::SPECIAL_PROCESS_FIX_REFERENCE;
        $self->_getReferenceController()->onRegister(function (ReferenceItem $item) use ($self, $fixRefProcess) {
            if (!$self->hasListen()) {
                return;
            }
            $collector = $self->getDutyCollector();
            $fix = $collector->getFix();
            $fix
                ->setName('Reference fix')
                ->setProcess($fixRefProcess)
                ->setUpdateData(array(
                    'reference' => $item->reference,
                    'group' => $item->group,
                    'dbVersion' => $item->dbVersion,
                    'id' => $item->id
            ));
            $collector->registerFix($fix);
        });
    }

    static public function commitDutyChanges() {
        $self = self::getInstance();
        if (!$self->_dutyCollector) {
            return null;
        }
        $fixes = $self->getDutyCollector()->getUsesFixed();
        if (!$fixes) {
            return;
        }
        $setupLog = $self->_createSetupLog();
        foreach ($fixes as $fix) {
            $applyLog = new AppliedChangesLogModel();
            $applyLog->subjectName = $fix->getSubject();
            $applyLog->groupLabel = $fix->getLabel();
            $applyLog->processName = $fix->getProcess();
            $applyLog->description = $fix->getName();
            $applyLog->originalData = $fix->getOriginalData();
            $applyLog->updateData = $fix->getUpdateData();
            $applyLog->success = true;
            $applyLog->setSetupLog($setupLog);
            $applyLog->save();
        }
        $self->getDutyCollector()->commit($self->getDbVersion());
    }

    /**
     * @param $path
     * @throws \Exception
     * @return Localization
     */
    public function getLocalization($path) {
        if (!isset($this->localizations[$path])) {
            $realPath = $this->localizePath.'/'.str_replace('.', '/',$path).'.php';
            if (!  file_exists($realPath)) {
                throw new \Exception('localization by path not found');
            }
            $this->localizations[$path] = new Localization(include $realPath);
        }
        return $this->localizations[$path];
    }

    /**
     * Meta description uses handlers, been register
     * @return array
     */
    protected function handlers() {
        return array(
            IblockHandler::className() => array(
                self::FIX_CHANGES_ADD_KEY => array('iblock', 'OnAfterIBlockAdd'),
                self::FIX_CHANGES_BEFORE_CHANGE_KEY => array('iblock', 'OnBeforeIBlockUpdate'),
                self::FIX_CHANGES_AFTER_CHANGE_KEY => array('iblock', 'OnAfterIBlockUpdate'),
                self::FIX_CHANGES_BEFORE_DELETE_KEY => array('iblock', 'OnBeforeIBlockDelete'),
                self::FIX_CHANGES_AFTER_DELETE_KEY => array('iblock', 'OnIBlockDelete'),
            ),
            IblockPropertyHandler::className() => array(
                self::FIX_CHANGES_ADD_KEY => array('iblock', 'OnAfterIBlockPropertyAdd'),
                self::FIX_CHANGES_BEFORE_CHANGE_KEY => array('iblock', 'OnBeforeIBlockPropertyUpdate'),
                self::FIX_CHANGES_AFTER_CHANGE_KEY => array('iblock', 'OnAfterIBlockPropertyUpdate'),
                self::FIX_CHANGES_BEFORE_DELETE_KEY => array('iblock', 'OnBeforeIBlockPropertyDelete'),
                self::FIX_CHANGES_AFTER_DELETE_KEY => array('iblock', 'OnIBlockPropertyDelete')
            ),
            IblockSectionHandler::className() => array(
                self::FIX_CHANGES_ADD_KEY => array('iblock', 'OnAfterIBlockSectionAdd'),
                self::FIX_CHANGES_BEFORE_CHANGE_KEY => array('iblock', 'OnBeforeIBlockSectionUpdate'),
                self::FIX_CHANGES_AFTER_CHANGE_KEY => array('iblock', 'OnAfterIBlockSectionUpdate'),
                self::FIX_CHANGES_BEFORE_DELETE_KEY => array('iblock', 'OnBeforeIBlockSectionDelete'),
                self::FIX_CHANGES_AFTER_DELETE_KEY => array('iblock', 'OnAfterIBlockSectionDelete')
            )
        );
    }

    /**
     * @return BaseSubjectHandler[]
     */
    public function getSubjectHandlers() {
        $classes = array_keys($this->handlers());
        $res = array();
        foreach ($classes as $class) {
            $res[] = $this->getSubjectHandler($class);
        }
        return $res;
    }

    /**
     * @param $class
     * @return BaseSubjectHandler
     */
    public function getSubjectHandler($class) {
        if (! class_exists($class)) {
            foreach (array_keys($this->handlers()) as $handlerClass) {
                $arClassName = explode('\\', $handlerClass);
                if ($class == array_pop($arClassName)) {
                    $class = $handlerClass;
                    break;
                }
            }
        }
        if (!$this->_handlers[$class]) {
            $this->_handlers[$class] = new $class($this->_getReferenceController());
        }
        return $this->_handlers[$class];
    }

    /**
     * @return AddProcess
     */
    private function _getProcessAdd() {
        if (!$this->_processAdd) {
            $this->_processAdd = new AddProcess();
        }
        return $this->_processAdd;
    }

    /**
     * @return UpdateProcess
     */
    private function _getProcessUpdate() {
        if (!$this->_processUpdate) {
            $this->_processUpdate = new UpdateProcess();
        }
        return $this->_processUpdate;
    }

    /**
     * @return DeleteProcess
     */
    private function _getProcessDelete() {
        if (!$this->_processDelete) {
            $this->_processDelete = new DeleteProcess();
        }
        return $this->_processDelete;
    }

    /**
     * @param $class
     * @return BaseProcess
     */
    public function getProcess($class) {
        switch ($class) {
            case AddProcess::className():
                return $this->_getProcessAdd();
                break;
            case UpdateProcess::className():
                return $this->_getProcessUpdate();
                break;
            case DeleteProcess::className():
                return $this->_getProcessDelete();
                break;
        }
    }

    public function handle($handlerClass, $eventKey, $params) {
        if (!$this->hasListen()) {
            return ;
        }
        $handlers = $this->handlers();
        if ( !$handlers[$handlerClass][$eventKey]) {
            return false;
        }
        $collector = $this->getDutyCollector();
        $handler = $this->getSubjectHandler($handlerClass);
        if (!$handler->required() && !$this->getOptions()->isEnableSubjectHandler($handlerClass)) {
            return ;
        }

        $fix  = $collector->getFix();
        $fix->setSubject(get_class($handler));

        $result = false;
        switch ($eventKey) {
            case self::FIX_CHANGES_ADD_KEY:
                $process = $this->_getProcessAdd();
                $fix
                    ->setProcess(get_class($process))
                    ->setName($handler->getName().'. '.$process->getName());
                $result = $process->change($handler, $fix, $params);
                break;
            case self::FIX_CHANGES_BEFORE_CHANGE_KEY:
                $process = $this->_getProcessUpdate();
                $process->beforeChange($handler, $params);
                break;
            case self::FIX_CHANGES_AFTER_CHANGE_KEY:
                $process = $this->_getProcessUpdate();
                $fix
                    ->setProcess(get_class($process))
                    ->setName($handler->getName().'. '.$process->getName());
                $result = $process->afterChange($handler, $fix, $params);
                break;
            case self::FIX_CHANGES_BEFORE_DELETE_KEY:
                $process = $this->_getProcessDelete();
                $process->beforeChange($handler, $params);
                break;
            case self::FIX_CHANGES_AFTER_DELETE_KEY:
                $process = $this->_getProcessDelete();
                $fix
                    ->setProcess(get_class($process))
                    ->setName($handler->getName().'. '.$process->getName());
                $result = $process->afterChange($handler, $fix, $params);
                break;
        }
        $result && $collector->registerFix($fix);
    }

    /**
     * @return Collector
     */
    private function _createCollector() {
        return Collector::createInstance($this->_getFixFilesDir(), $this);
    }

    /**
     * @return Collector
     */
    public function getDutyCollector() {
        if (!$this->_dutyCollector) {
            $this->_dutyCollector = $this->_createCollector();
        }
        return $this->_dutyCollector;
    }

    private function _getReferenceController() {
        if (!$this->_referenceController) {
            $this->_referenceController = new ReferenceController($this->getDbVersion());
        }
        return $this->_referenceController;
    }

    /**
     * @param Collector $collector
     * @return $this
     */
    public function injectDutyCollector(Collector $collector) {
        $this->_dutyCollector = $collector;
        return $this;
    }

    /**
     * Directory location fixed files
     * @return string
     */
    private function _getFixFilesDir() {
        return Application::getDocumentRoot().DIRECTORY_SEPARATOR.$this->getOptions()->catalogPath;
    }

    /**
     * @return Collector[]
     */
    public function getNotAppliedCollectors() {

        $result = AppliedChangesLogTable::getList(array(
            'select' => array('GROUP_LABEL'),
            'group' => array('GROUP_LABEL')
        ));
        $usesGroups = array_map(function ($row) {
            return $row['GROUP_LABEL'];
        }, $result->fetchAll());
        $dir = new Directory($this->_getFixFilesDir());
        $collectors = array();
        foreach ($dir->getChildren() as $file) {
            if ($file->isDirectory()) {
                continue;
            }
            if (in_array($file->getName(), $usesGroups)) {
                continue;
            }
            $collectors[] = Collector::createByFile($file->getPath(), $this);
        }
        return $collectors;
    }

    /**
     * @return CollectorFix[]
     */
    public function getNotAppliedFixes() {
        $collectors = $this->getNotAppliedCollectors();
        $result = array();
        foreach ($collectors as $collector) {
            $result = array_merge($result, $collector->getFixes() ?: array());
        }
        return $result;
    }


    private function _applyFix(CollectorFix $fix, SetupLogModel $setupLog = null) {
        $setupLog = $setupLog ?: $this->_createSetupLog();
        $applyFixLog = new AppliedChangesLogModel();
        $applyFixLog->processName = $fix->getProcess();
        $applyFixLog->subjectName = $fix->getSubject();
        $applyFixLog->setSetupLog($setupLog);
        $applyFixLog->groupLabel = $fix->getLabel();

        $process = $this->getProcess($fix->getProcess());
        $subjectHandler = $this->getSubjectHandler($fix->getSubject());
        if (!$subjectHandler->required() && !$this->getOptions()->isEnableSubjectHandler($fix->getSubject())) {
            return ;
        }

        $result = $process->update($subjectHandler, $fix, $applyFixLog);
        $applyFixLog->success = (bool) $result->isSuccess();
        !$result->isSuccess() && $applyFixLog->description .= '. '.$result->getMessage();
        $applyFixLog->save();
    }

    private function _applyReferenceFix(CollectorFix $fix) {
        $item = new ReferenceItem();
        $data = $fix->getUpdateData();

        $item->reference = $data['reference'];
        $item->id = $data['id'];
        $item->group = $data['group'];
        $item->dbVersion = $data['dbVersion'];

        !$this->_getReferenceController()->getReferenceValue($item->id, $item->group, $item->dbVersion)
            &&
        !$this->_getReferenceController()->getItemCurrentVersionByReference($item->reference)
            &&
        $this->_getReferenceController()->registerItem($item);
    }


    public function applyFixesList($fixes) {
        if (!$fixes) {
            return 0;
        }
        $this->_disableListen();
        foreach ($fixes as $fix) {
            if ($fix->getProcess() == self::SPECIAL_PROCESS_FIX_REFERENCE) {
                $this->_applyReferenceFix($fix);
                continue;
            }
            $this->_applyFix($fix);
        }
        $this->_enableListen();
        return count($fixes);
    }

    /**
     * Применение фиксации
     * @return int
     */
    public function applyNewFixes() {
        return $this->applyFixesList($this->getNotAppliedFixes());
    }

    /**
     * @return SetupLogModel
     */
    private function _createSetupLog() {
        $setupLog = new SetupLogModel();
        $setupLog->userId = $this->getCurrentUser()->GetID();
        $setupLog->save();
        return $setupLog;
    }

    /**
     * @return \CUser
     */
    public function getCurrentUser() {
        global $USER;
        return $USER ?: new \CUser();
    }

    /**
     * @return SetupLogModel
     */
    public function getLastSetupLog() {
        return SetupLogModel::findOne(array(
            'order' => array('date' => 'desc')
        ));
    }

    /**
     * Откат изменений последней фиксации.
     * @return null
     */
    public function rollbackLastChanges() {
        $setupLog = $this->getLastSetupLog();
        if (!$setupLog) {
            return null;
        }
        $this->rollbackByLogs($setupLog->getAppliedLogs() ?: array());
        $setupLog->delete();
    }

    /**
     * Откат списка логированных изменений
     * @param AppliedChangesLogModel[] $list
     * @return null
     */
    public function rollbackByLogs($list) {
        $this->_disableListen();
        foreach ($list as $log) {
            $log->delete();
            if (!$log->success) {
                continue;
            }
            $process = $this->getProcess($log->processName);
            $subjectHandler = $this->getSubjectHandler($log->subjectName);
            $process->rollback($subjectHandler, $log);
        }
        $this->_enableListen();
    }


    /**
     * @return string
     */
    public function getDbVersion() {
        $options = self::getOptions();
        !$options->version && $options->version = md5(time());
        return $options->version;
    }

    /**
     * Export data json format
     * @return string
     */
    public function getExportText() {
        $handlerClasses = array_keys($this->handlers());
        $collector = $this->_createCollector();
        // version export
        foreach ($this->_getReferenceController()->getItems() as $item) {
            $fix = $collector->getFix();
            $fix
                ->setName('Reference fix')
                ->setProcess(self::SPECIAL_PROCESS_FIX_REFERENCE)
                ->setUpdateData(array(
                    'reference' => $item->reference,
                    'group' => $item->group,
                    'dbVersion' => $item->dbVersion,
                    'id' => $item->id
                ));
            $collector->registerFix($fix);
        }

        // entities scheme export
        foreach ($handlerClasses as $class) {
            $handler = $this->getSubjectHandler($class);
            $ids = $handler->existsIds();
            foreach ($ids as $id) {
                $snapshot = $handler->getSnapshot($id);
                $fix = $collector->getFix();
                $fix->setSubject(get_class($handler))
                    ->setProcess(self::SPECIAL_PROCESS_FULL_MIGRATE)
                    ->setDbVersion($this->getDbVersion())
                    ->setUpdateData($snapshot);
                $collector->registerFix($fix);
            }
        }
        return arrayToJson($collector->getFixesData($this->getDbVersion()));
    }

    /**
     * Refresh current DB version, copy references links
     */
    public function runRefreshVersion() {
        $cloneVersion = md5(time());
        $registerResult = $this->_getReferenceController()->registerCloneVersion($cloneVersion);
        if (!$registerResult) {
            return false;
        }
        self::getOptions()->version = $cloneVersion;
        $this->_referenceController = null;
        return true;
    }

    public function import($json) {
        $data = jsonToArray($json);
        // Коллектор проинициализировать без участия файлов
        $collector = $this->_createCollector($data);
        foreach ($collector->getFixes() as $fix) {
            $subjectHandlerClass = $fix->getSubject();
            if (!$subjectHandlerClass) {
                // в первую очередь обработка версий
                $this->_applyReferenceFix($fix);
                continue;
            }
            // обработчиками применить снимки ПО
            $this->_applyFix($fix);
        }
    }

    public function clearReferences() {
        $this->_getReferenceController()->deleteAll();
    }
}

function jsonToArray($json) {
    global $APPLICATION;
    $value = json_decode($json, true);
    $value = $APPLICATION->ConvertCharsetArray($value, "UTF-8", LANG_CHARSET);
    return $value;
}

function arrayToJson($data) {
    global $APPLICATION;
    $data = $APPLICATION->ConvertCharsetArray($data, LANG_CHARSET, "UTF-8");
    return json_encode($data);
}
