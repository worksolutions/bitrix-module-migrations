<?php

namespace WS\Migrations\Console\Command;

class ListCommand extends BaseCommand{

    private $registeredFixes;
    /** @var  \WS\Migrations\Localization */
    private $localization;

    protected function initParams($params) {
        $this->registeredFixes = array();
        $this->localization = \WS\Migrations\Module::getInstance()->getLocalization('admin')->fork('cli');
    }

    public function execute($callback = false) {
        $has = false;
        foreach ($this->module->getNotAppliedFixes() as $notAppliedFix) {
            $this->registerFix($notAppliedFix->getName());
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
        if ($name == 'Reference fix') {
            $name = $this->localization->message('common.reference-fix');
        }
        $this->registeredFixes[$name]++;
    }

    private function printRegisteredFixes() {
        foreach ($this->registeredFixes as $name => $count) {
            $count = $count > 1 ? '['.$count.']' : '';
            $this->console->printLine($name.' '.$count);
        }
    }


}
