<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations;


abstract class ChangeHandler {
    static public function className() {
        return get_called_class();
    }

    /**
     * @return Localization
     */
    protected function getLocalization() {
        return Module::getInstance()->getLocalization('handlers');
    }

    /**
     * Name of Handler in Web interface
     * @return string
     */
    abstract public function getName();

    /**
     * uses by before model changes
     * @param $data
     */
    public function beforeChange($data) {
    }

    /**
     * uses by after model changes
     * @param $data
     * @param Catcher $catcher
     */
    public function afterChange($data, Catcher $catcher) {
    }

    /**
     * uses by after model changes
     * @param $data
     * @param Catcher $catcher
     */
    public function change($data, Catcher $catcher) {
        $catcher->fixChangeData($data);
    }

    /**
     * uses by import changes
     * @param Catcher $catcher
     */
    public function update(Catcher $catcher) {
        $data = $catcher->getChangeData();
    }

    /**
     * uses by rollback import changes
     * @param Catcher $catcher
     */
    public function rollback(Catcher $catcher) {
        $catcher->getOriginalData();
    }
}
