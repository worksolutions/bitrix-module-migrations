<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\ChangeDataCollector;


class CollectorFix {
    private $_isUses = false;
    private $_subject;
    private $_process;
    private $_data;

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
        return true;
    }


    public function getProcess() {
        return $this->_process;
    }


    public function getSubject() {
        return $this->_subject;
    }

    /**
     * @return mixed
     */
    public function getData() {
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
    public function setData($data) {
        $this->_data = $data;
        return $this;
    }
} 