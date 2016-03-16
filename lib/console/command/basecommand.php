<?php

namespace WS\Migrations\Console\Command;

use WS\Migrations\Console\Console;
use WS\Migrations\Module;

abstract class BaseCommand {
    /** @var Console  */
    protected $console;
    /** @var Module  */
    protected $module;

    public function __construct(Console $console, $params) {
        $this->console = $console;
        $this->initParams($params);
        $this->module = Module::getInstance();
    }

    /**
     * @return string
     */
    static public function className() {
        return get_called_class();
    }
    protected function initParams($params) {}

    abstract public function execute($callback = false);

}
