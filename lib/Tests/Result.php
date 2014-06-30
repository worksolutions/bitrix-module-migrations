<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Tests;


class Result {
    private $_success;
    private $_message;

    /**
     * @return mixed
     */
    public function getMessage() {
        return $this->_message;
    }

    /**
     * @param mixed $value
     */
    public function setMessage($value) {
        $this->_message = $value;
        return $this;
    }

    /**
     * @return mixed
     */
    public function isSuccess() {
        return $this->_success;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setSuccess($value) {
        $this->_success = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray() {
        return array(
            'STATUS' => $this->isSuccess(),
            'MESSAGE' => array(
                'PREVIEW' => str_replace("\n", "<br/>", $this->getMessage())
            )
        );
    }

}