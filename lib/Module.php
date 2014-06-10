<?php

namespace WS\Migrations;
use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\UserTable;
use WS\Migrations\ChangeDataCollector\Collector;
use WS\Migrations\ChangeDataCollector\CollectorFix;
use WS\Migrations\Entities\AppliedChangesLogModel;
use WS\Migrations\Entities\AppliedChangesLogTable;
use WS\Migrations\Entities\SetupLogModel;
use WS\Migrations\Processes\AddProcess;
use WS\Migrations\Processes\BaseProcess;
use WS\Migrations\Processes\DeleteProcess;
use WS\Migrations\Processes\UpdateProcess;
use WS\Migrations\SubjectHandlers\BaseSubjectHandler;
use WS\Migrations\SubjectHandlers\IblockHandler;
use WS\Migrations\SubjectHandlers\IblockPropertyHandler;
use WS\Migrations\SubjectHandlers\IblockSectionHandler;

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

    private $localizePath = null,
            $localizations = array();

    private static $name = 'ws.migrations';

    private $_handlers = array();

    private $_dutyCollector = null;

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
    private function _hasListen() {
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
    }

    static public function commitDutyChanges() {
        $self = self::getInstance();
        if (!$self->_dutyCollector) {
            return null;
        }
        $fixes = $self->_getDutyCollector()->getUsesFixed();
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
        $self->_getDutyCollector()->commit();
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
            $this->_handlers[$class] = new $class;
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
        if (!$this->_hasListen()) {
            return ;
        }
        $handlers = $this->handlers();
        if ( !$handlers[$handlerClass][$eventKey]) {
            return false;
        }
        $collector = $this->_getDutyCollector();
        $handler = $this->getSubjectHandler($handlerClass);

        $fix  = $collector->getFix();
        $fix->setSubject(get_class($handler));

        switch ($eventKey) {
            case self::FIX_CHANGES_ADD_KEY:
                $process = $this->_getProcessAdd();
                $fix
                    ->setProcess(get_class($process))
                    ->setName($handler->getName().'. '.$process->getName());
                $process->change($handler, $fix, $params);
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
                $process->afterChange($handler, $fix, $params);
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
                $process->afterChange($handler, $fix, $params);
                break;
        }
    }

    /**
     * @return Collector
     */
    private function _getDutyCollector() {
        if (!$this->_dutyCollector) {
            $this->_dutyCollector = Collector::createInstance($this->_getFixFilesDir(), $this);
        }
        return $this->_dutyCollector;
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

    /**
     * Применение фиксации
     * @return int
     */
    public function applyNewFixes() {
        $this->_disableListen();
        $fixes = $this->getNotAppliedFixes();
        if (!$fixes) {
            return 0;
        }
        $setupLog = $this->_createSetupLog();

        foreach ($fixes as $fix) {
            $applyFixLog = new AppliedChangesLogModel();
            $applyFixLog->processName = $fix->getProcess();
            $applyFixLog->subjectName = $fix->getSubject();
            $applyFixLog->setSetupLog($setupLog);
            $applyFixLog->groupLabel = $fix->getLabel();

            $process = $this->getProcess($fix->getProcess());
            $subject = $this->getSubjectHandler($fix->getSubject());

            $result = $process->update($subject, $fix, $applyFixLog);
            $applyFixLog->success = (bool) $result->isSuccess();
            !$result->isSuccess() && $applyFixLog->description .= '. '.$result->getMessage();
            $applyFixLog->save();
        }
        $this->_enableListen();
        return count($fixes);
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
     * @return null
     */
    public function rollbackLastChanges() {
        $this->_disableListen();
        $setupLog = $this->getLastSetupLog();
        if (!$setupLog) {
            return null;
        }
        foreach ($setupLog->getAppliedLogs() as $log) {
            $log->delete();
            if (!$log->success) {
                continue;
            }
            $process = $this->getProcess($log->processName);
            $subjectHandler = $this->getSubjectHandler($log->subjectName);
            $process->rollback($subjectHandler, $log);
        }
        $setupLog->delete();
        $this->_enableListen();
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
