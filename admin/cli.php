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

$colors = array(
    'black' => 30,
    'red' => 31,
    'green' => 32,
    'yellow' => 33,
    'blue' => 34,
    'magenta' => 35,
    'cyan' => 36,
    'white' => 37,
    'default' => 0
);

$colorize = function ($text, $color) use ($colors) {
    $colorVal = isset($colors[$color]) ? $colors[$color] : $colors['default'];
    return chr(27) . "[{$colorVal}m" . $text . chr(27) . "[0m";
};

$print = function ($str, $color = false) use (& $out, & $colorize) {
    $color && $str = $colorize($str, $color);
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
$migrationNumber = 0;
$migrationCount = 0;
$start = 0;
$showProgress = function ($data, $type) use (& $print, & $migrationNumber, & $migrationCount, &$start) {
    if ($type == 'setCount') {
        $migrationCount = $data;
    }
    if ($type == 'start') {
        $migrationNumber++;
        $print("Start: {$data['name']}({$migrationNumber}/$migrationCount)", 'yellow');
    }
    if ($type == 'end') {
        /**@var \WS\Migrations\Entities\AppliedChangesLogModel $log */
        $log = $data['log'];
        $time = round($data['time'], 2);
        $message = 'End: '. $log->description;
        if (isset($data['count'])) {
            $message .= "[{$data['count']}]";
        }
        $overallTime = round(microtime(true) - $start, 2);
        $message .= ": $time sec ($overallTime sec)";
        $print($message, $log->success ? 'green' : 'red');
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
        $has && $printRegisteredFixes();
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
        if (!$diagnosticTester->run()) {
            $print("Diagnostic is not valid");
            exit();
        }
        $print("Applying new fixes started....", 'yellow');
        $time = time();
        $start = microtime(true);
        $count = (int)$module->applyNewFixes($showProgress);
        $interval = time() - $time;
        $print("Apply action finished! $count items, time $interval sec", 'yellow');
        break;
    case "rollback":
        $print("Are you sure? (yes|no):");
        $answer = $readln();
        if ($answer != 'yes') {
            exit();
        }
        $print("Rollback action started...", 'yellow');
        $start = microtime(true);
        $module->rollbackLastChanges($showProgress);
        $interval = round(microtime(true) - $start, 2);
        $print("Rollback action finished! Time $interval sec", 'yellow');
        break;
    default:
        $print("Action `$action` is not supported");
        exit();
}
