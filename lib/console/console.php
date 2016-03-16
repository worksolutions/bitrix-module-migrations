<?php

namespace WS\Migrations\Console;

use WS\Migrations\Console\Command\ApplyCommand;
use WS\Migrations\Console\Command\BaseCommand;
use WS\Migrations\Console\Command\CreateScenarioCommand;
use WS\Migrations\Console\Command\HelpCommand;
use WS\Migrations\Console\Command\LastCommand;
use WS\Migrations\Console\Command\ListCommand;
use WS\Migrations\Console\Command\RollbackCommand;
use WS\Migrations\Console\Formatter\Output;

class Console {
    const OUTPUT_ERROR = 'error';
    const OUTPUT_PROGRESS = 'progress';
    const OUTPUT_SUCCESS = 'success';
    /**
     * @var resource
     */
    private $out;
    private $action;
    /** @var  Output */
    private $successOutput;
    /** @var  Output */
    private $errorOutput;
    /** @var  Output */
    private $progressOutput;
    /** @var  Output */
    private $defaultOutput;

    public function __construct($args) {
        global $APPLICATION;
        $APPLICATION->ConvertCharsetArray($args, "UTF-8", LANG_CHARSET);
        $this->out = fopen('php://stdout', 'w');
        array_shift($args);
        $this->params = $args;
        $this->action = isset($this->params[0]) ? $this->params[0] : '--help';
        $this->successOutput = new Output('green');
        $this->errorOutput = new Output('red');
        $this->progressOutput = new Output('yellow');
        $this->defaultOutput = new Output();
    }

    /**
     * @param $str
     * @param $type
     * @return Console
     */
    public function printLine($str, $type = false) {
        global $APPLICATION;
        $str = $APPLICATION->ConvertCharset($str, LANG_CHARSET, "UTF-8");
        if ($type) {
            $str = $this->getOutput($type)->colorize($str);
        }
        fwrite($this->out, $str . "\n");
        return $this;
    }

    public function readLine() {
        return trim(fgets(STDIN));
    }

    /**
     * @return BaseCommand
     * @throws ConsoleException
     */
    public function getCommand() {
        $commands = array(
            '--help' => HelpCommand::className(),
            'list' => ListCommand::className(),
            'apply' => ApplyCommand::className(),
            'rollback' => RollbackCommand::className(),
            'last' => LastCommand::className(),
            'createScenario' => CreateScenarioCommand::className(),
        );
        if (!$commands[$this->action]) {
            throw new ConsoleException("Action `{$this->action}` is not supported");
        }
        return new $commands[$this->action]($this, $this->params);
    }

    /**
     * @param $type
     * @return Output
     */
    private function getOutput($type) {
        switch ($type) {
            case 'success':
                return $this->successOutput;
                break;
            case 'error':
                return $this->errorOutput;
                break;
            case 'progress':
                return $this->progressOutput;
                break;
            default:;
        }
        return $this->defaultOutput;
    }

}
