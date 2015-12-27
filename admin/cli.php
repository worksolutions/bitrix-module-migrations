<?php

$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define('CHK_EVENT', true);

@set_time_limit(0);

/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */
$out = fopen('php://stdout', 'w');
$print = function ($str) use (& $out) {
    fwrite($out, $str."\n");
};
$readln = function () {
    return trim(fgets(STDIN));
};

$registeredFixes = array();
$registerFix = function ($name) use (& $registeredFixes) {
    $registeredFixes[$name]++;
};
$printRegisteredFixes = function () use (& $registeredFixes, $print) {
    foreach ($registeredFixes as $name => $count) {
        $count = $count > 1 ? '['.$count.']' : '';
        $print($name.' '.$count);
    }
};

$print("");

array_shift($argv);
if (empty($argv)) {
    $print("Call params are empty");
    exit();
}
$params = $argv;
if ($params[0] == '--help') {
    $print("Action list:");
    $print("* list List of new migrations");
    $print("* apply Apply new migrations, \n   -f Without approve");
    $print("* rollback Rollback last applied migrations");
    exit();
}
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once(__DIR__."/../include.php");
require_once(__DIR__."/../prolog.php");

$hasForce = in_array('-f', $params);
$module = \WS\Migrations\Module::getInstance();
$action = $params[0];
switch ($action) {
    case "list":
        $has = false;
        foreach ($module->getNotAppliedFixes() as $notAppliedFix) {
            $registerFix($notAppliedFix->getLabel());
            $has = true;
        }
        foreach ($module->getNotAppliedScenarios() as $notAppliedScenario) {
            $registerFix($notAppliedScenario::name());
            $has = true;
        }
        !$has && $print("Nothing to apply");
        break;
    case "apply":
        if (!$hasForce) {
            $print("Are you sure? (yes|no):");
            $answer = $readln();
            if ($answer != 'yes') {
                exit();
            }
        }
        $diagnosticTester = $module->getDiagnosticTester();
        if ($diagnosticTester->run()) {
            $print("Diagnostic is not valid");
            exit();
        }
        $print("Applying new fixes is started....");
        $time = time();
        $count = (int)$module->applyNewFixes();
        $interval = time() - $time;
        $print("Apply action is finished! $count items, time $interval sec");
        break;
    case "rollback":
        $print("Are you sure? (yes|no):");
        $answer = $readln();
        if ($answer != 'yes') {
            exit();
        }
        $module->rollbackLastChanges();
        $print("Rollback action is finished!");
        break;
    default:
        $print("Action `$action` is not supported");
        exit();
}
