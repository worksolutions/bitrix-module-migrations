<?php

namespace WS\Migrations\Console\Command;

use WS\Migrations\Console\Console;

class LastCommand extends BaseCommand {

    public function execute($callback = false) {
        $lastSetupLog = \WS\Migrations\Module::getInstance()->getLastSetupLog();
        if (!$lastSetupLog) {
            $this->console
                ->printLine("Nothing to show.");
            return;
        }
        $appliedFixes = array();
        $errorFixes = array();

        foreach ($lastSetupLog->getAppliedLogs() as $appliedLog) {
            !$appliedLog->success && $errorFixes[] = $appliedLog;
            $appliedLog->success && $appliedFixes[$appliedLog->description]++;
        }
        foreach ($appliedFixes as $fixName => $fixCount) {
            $this->console
                ->printLine($fixName . ($fixCount > 1 ? "[$fixCount]" : ""), Console::OUTPUT_SUCCESS);
        }
        /** @var \WS\Migrations\Entities\AppliedChangesLogModel $errorApply */
        foreach ($errorFixes as $errorApply) {
            $errorData = \WS\Migrations\jsonToArray($errorApply->description) ?: $errorApply->description;
            if (is_scalar($errorData)) {
                $this->console
                    ->printLine($errorData, Console::OUTPUT_ERROR);
            }
            if (is_array($errorData)) {
                $this->console
                    ->printLine($errorData['message'], Console::OUTPUT_ERROR);
            }

        }
    }
}
