<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations;


class Catcher {

    public function __construct() {
    }

    /**
     * @param array $value
     * @return $this
     */
    public function fixChangeData(array $value = null) {
    }

    /**
     * @return array | null
     */
    public function getChangeData() {
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