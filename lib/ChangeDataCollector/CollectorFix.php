<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\ChangeDataCollector;


use WS\Migrations\Processes\BaseProcess;
use WS\Migrations\SubjectHandlers\BaseSubjectHandler;

class CollectorFix {
    private $_subject;
    private $_process;
    private $_data;
    private $_label;
    private $_name;
    private $_originalData;

    private $_isUses = false;

    public function __construct($label) {
        $this->_label = $label;
    }

    /**
     * @return $this
     */
    public function take() {
        $this->_isUses = true;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUses() {
        return $this->_isUses;
    }


    /**
     * @return BaseProcess
     */
    public function getProcess() {
        return $this->_process;
    }


    /**
     * @return BaseSubjectHandler
     */
    public function getSubject() {
        return $this->_subject;
    }

    /**
     * @return mixed
     */
    public function getUpdateData() {
        return $this->_data;
    }

    /**
     * @param mixed $subject
     * @return $this
     */
    public function setSubject($subject) {
        $this->_subject = $subject;
        return $this;
    }

    /**
     * @param mixed $process
     * @return $this
     */
    public function setProcess($process) {
        $this->_process = $process;
        return $this;
    }

    /**
     * @param mixed $data
     * @return $this
     */
    public function setUpdateData($data) {
        $this->take();
        $this->_data = $data;
        return $this;
    }

    public function setOriginalData($data) {
        $this->_originalData = $data;
        return $this;
    }

    public function getOriginalData() {
        return $this->_originalData;
    }

    public function getLabel() {
        return $this->_label;
    }

    public function setName($value) {
        $this->_name = $value;
        return $this;
    }

    public function getName() {
        return $this->_name;
    }
}
