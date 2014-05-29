<?php
namespace WS\Migrations;
/**
 * @property string $catalogPath
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
}
