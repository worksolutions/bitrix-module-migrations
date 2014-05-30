<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations;

use Bitrix\Main\IO\File;

class Catcher {
    /**
     * @var File
     */
    private $_file;

    private function __construct(File $file, $log = null) {
        $this->_file = $file;
    }

    static public function createByFile($path) {
        return new static(new File($path));
    }

    static public function createByHandler($rootPath, $class) {
        $handlerClassName = explode('\\', $class);
        $filePath = $rootPath.'/'.time().'_'.array_pop($handlerClassName).'.json';
        return new static(new File($filePath));
    }

    public function createByLog() {
    }

    /**
     * @param array $value
     * @return $this
     */
    public function fixChangeData(array $value = null) {
        global $APPLICATION;
        $value = $APPLICATION->ConvertCharsetArray($value, LANG_CHARSET, "UTF-8");
        $this->_file->putContents(json_encode($value));
        return $this;
    }

    /**
     * @return array | null
     */
    public function getChangeData() {
        global $APPLICATION;
        $value = json_decode($this->_file->getContents(), true);
        $value = $APPLICATION->ConvertCharsetArray($value, "UTF-8", LANG_CHARSET);
        return $value;
    }

    /**
     * @param array $originalData
     * @return $this
     */
    public function fixUpdate(array $originalData = null) {
    }

    /**
     * @return array
     */
    public function getOriginalData() {
    }
}