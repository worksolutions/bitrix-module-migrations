<?php

/**
 * Class definition update migrations scenario actions
 **/
class #class_name# extends \WS\Migrations\ScriptScenario {

    /**
     * Name of scenario
     **/
    static public function name() {
        return #name#;
    }

    /**
     * Description of scenario
     **/
    static public function description() {
        return #description#;
    }

    /**
     * Write action by apply scenario. Use method `setData` for save need rollback data
     **/
    public function commit() {
        // my code
    }

    /**
     * Write action by rollback scenario. Use method `getData` for getting commit saved data
     **/
    public function rollback() {
        // my code
    }
}