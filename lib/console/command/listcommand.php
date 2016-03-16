<?php

namespace WS\Migrations\Console\Command;

class ListCommand extends BaseCommand{

    private $registeredFixes;

    protected function initParams($params) {
        $this->registeredFixes = array();
    }

    public function execute($callback = false) {
        $has = false;
        foreach ($this->module->getNotAppliedFixes() as $notAppliedFix) {
            $this->registerFix($notAppliedFix->getLabel());
            $has = true;
        }
        foreach ($this->module->getNotAppliedScenarios() as $notAppliedScenario) {
            $this->registerFix($notAppliedScenario::name());
            $has = true;
        }
        !$has && $this->console->printLine("Nothing to apply");
        $has && $this->printRegisteredFixes();
    }

    private function registerFix($name) {
        $this->registeredFixes[$name]++;
    }

    private function printRegisteredFixes() {
        foreach ($this->registeredFixes as $name => $count) {
            $count = $count > 1 ? '['.$count.']' : '';
            $this->console->printLine($name.' '.$count);
        }
    }


}
