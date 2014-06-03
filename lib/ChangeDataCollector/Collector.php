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

    private function __construct(File $file, $log = null) {
        $this->_file = $file;
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
        $fix = new CollectorFix();
        $this->_fixes[] = $fix;
        return $fix;
    }

    /**
     * @return array | null
     */
    private function _getSavedData() {
        global $APPLICATION;
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
                'data' => $fix->getData()
            );
        }
        if (!$fixesData) {
            return false;
        }
        $this->_saveData($fixesData);
        return true;
    }

}