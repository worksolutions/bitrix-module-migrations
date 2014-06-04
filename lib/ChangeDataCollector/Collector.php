<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\ChangeDataCollector;

use Bitrix\Main\IO\File;

class Collector {
    /**
     * @var File
     */
    private $_file;

    /**
     * @var CollectorFix[]
     */
    private $_fixes;

    private $_label;

    private function __construct(File $file) {
        $this->_file = $file;
        $this->_label = $file->getName();
        $savedData = $this->_getSavedData();
        foreach ($savedData as $arFix) {
            $fix = $this->getFix();
            $fix
                ->setData($arFix['data'])
                ->setSubject($arFix['subject'])
                ->setProcess($arFix['process'])
                ->setName($arFix['name']);
        }
    }

    static public function createByFile($path) {
        return new static(new File($path));
    }

    static public function createInstance($dir) {
        if (!file_exists($dir)) {
            throw new \Exception("Dir `$dir` not exists");
        }
        $fileName = time().'.json';
        return self::createByFile($dir.DIRECTORY_SEPARATOR.$fileName);
    }

    /**
     * @param array $value
     * @return $this
     */
    private function _saveData(array $value = null) {
        global $APPLICATION;
        $value = $APPLICATION->ConvertCharsetArray($value, LANG_CHARSET, "UTF-8");
        $this->_file->putContents(json_encode($value));
        return $this;
    }

    /**
     * @return CollectorFix
     */
    public function getFix() {
        $fix = new CollectorFix($this->_label);
        $this->_fixes[] = $fix;
        return $fix;
    }

    /**
     * @return array | null
     */
    private function _getSavedData() {
        global $APPLICATION;
        if (!$this->_file->isExists()) {
            return array();
        }
        $value = json_decode($this->_file->getContents(), true);
        $value = $APPLICATION->ConvertCharsetArray($value, "UTF-8", LANG_CHARSET);
        return $value;
    }

    public function commit() {
        $fixesData = array();
        foreach ($this->_fixes as $fix) {
            if (!$fix->isUses()) {
                continue;
            }
            $fixesData[] = array(
                'process' => $fix->getProcess(),
                'subject' => $fix->getSubject(),
                'data' => $fix->getData(),
                'name' => $fix->getName()
            );
        }
        $this->_fixes = array();
        if (!$fixesData) {
            return false;
        }
        $this->_saveData($fixesData);
        return true;
    }

    /**
     * @return CollectorFix[]
     */
    public function getFixes() {
        return $this->_fixes;
    }
}
