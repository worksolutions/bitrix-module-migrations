<?php
namespace WS\Migrations;
/**
 * @property string $catalogPath
 * @property string $version;
 * @property string $useAutotests;
 * @property string $enabledSubjectHandlers;
 * @author <sokolovsky@worksolutions.ru>
 */
final class ModuleOptions {
    private $_moduleName = 'ws.migrations';

    private function __construct() {
    }

    /**
     * @staticvar self $self
     * @return Options
     */
    public function getInstance() {
        static $self = null;
        if (!$self) {
            $self = new self;
        }
        return $self;
    }

    private function _setValue($name, $value) {
        \COption::SetOptionString($this->_moduleName, $name, serialize($value));
    }

    private function _getValue($name) {
        $value = \COption::GetOptionString($this->_moduleName, $name);
        return unserialize($value);
    }

    public function __set($name, $value) {
        $this->_setValue($name, $value);
        return $value;
    }

    public function __get($name) {
        return $this->_getValue($name);
    }

    /**
     * @param $class
     */
    public function disableSubjectHandler($class) {
        $this->enabledSubjectHandlers = array_diff($this->enabledSubjectHandlers ?: array(), array($class));
    }

    /**
     * @param $class
     */
    public function enableSubjectHandler($class) {
        $this->enabledSubjectHandlers = array_unique(array_merge($this->enabledSubjectHandlers ?: array(), array($class)));
    }

    /**
     * @param $class
     * @return bool
     */
    public function isEnableSubjectHandler($class) {
        return in_array($class, $this->enabledSubjectHandlers);
    }
}
