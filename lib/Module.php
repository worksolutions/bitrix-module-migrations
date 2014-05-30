<?php

namespace WS\Migrations;
use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use WS\Migrations\Handlers\Iblock\IblockAdd;
use WS\Migrations\Handlers\Iblock\IblockDelete;
use WS\Migrations\Handlers\Iblock\IblockUpdate;
use WS\Migrations\Handlers\IblockProperty\IblockPropertyAdd;
use WS\Migrations\Handlers\IblockProperty\IblockPropertyDelete;
use WS\Migrations\Handlers\IblockProperty\IblockPropertyUpdate;

/**
 * Class Module (Singletone)
 *
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */
class Module {

    const FIX_CHANGES_BEFORE_KEY = 'before';
    const FIX_CHANGES_AFTER_KEY = 'after';
    const FIX_CHANGES_KEY = 'change';

    private $localizePath = null,
            $localizations = array();

    private static $name = 'ws.migrations';

    private $_handlers = array();

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
     * @staticvar self $self
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

    /**
     * @param str $path
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
            IblockAdd::className() => array(
                self::FIX_CHANGES_KEY => array('iblock', 'OnAfterIBlockAdd')
            ),
            IblockUpdate::className() => array(
                self::FIX_CHANGES_BEFORE_KEY => array('iblock', 'OnBeforeIBlockUpdate'),
                self::FIX_CHANGES_AFTER_KEY => array('iblock', 'OnAfterIBlockUpdate'),
            ),
            IblockDelete::className() => array(
                self::FIX_CHANGES_BEFORE_KEY => array('iblock', 'OnBeforeIBlockDelete'),
                self::FIX_CHANGES_AFTER_KEY => array('iblock', 'OnIBlockDelete'),
            ),
            IblockPropertyAdd::className() => array(
                self::FIX_CHANGES_KEY => array('iblock', 'OnAfterIBlockPropertyAdd')
            ),
            IblockPropertyUpdate::className() => array(
                self::FIX_CHANGES_BEFORE_KEY => array('iblock', 'OnBeforeIBlockElementUpdate'),
                self::FIX_CHANGES_AFTER_KEY => array('iblock', 'OnAfterIBlockPropertyUpdate'),
            ),
            IblockPropertyDelete::className() => array(
                self::FIX_CHANGES_BEFORE_KEY => array('iblock', 'OnBeforeIBlockPropertyDelete'),
                self::FIX_CHANGES_AFTER_KEY => array('iblock', 'OnIBlockPropertyDelete'),
            )
        );
    }

    /**
     * @param $class
     * @return ChangeHandler
     */
    private function _getHandler($class) {
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
     * @param ChangeHandler $handler
     * @return Catcher
     */
    private function _createCatcher($handler) {
        return Catcher::createByHandler($this->_getFixFilesDir(), get_class($handler));
    }

    public function handle($handlerClass, $eventKey, $params) {
        $handlers = $this->handlers();
        if ( !$handlers[$handlerClass][$eventKey]) {
            return false;
        }
        $handler = $this->_getHandler($handlerClass);
        switch ($eventKey) {
            case self::FIX_CHANGES_BEFORE_KEY:
                $handler->beforeChange($params);
                break;
            case self::FIX_CHANGES_AFTER_KEY:
                $catcher = $this->_createCatcher($handler);
                $handler->afterChange($params, $catcher);
                break;
            case self::FIX_CHANGES_KEY:
                $catcher = $this->_createCatcher($handler);
                $handler->change($params, $catcher);
                break;
        }
    }

    /**
     * Directory location fixed files
     * @return string
     */
    private function _getFixFilesDir() {
        return Application::getDocumentRoot().DIRECTORY_SEPARATOR.$this->getOptions()->catalogPath;
    }
}
