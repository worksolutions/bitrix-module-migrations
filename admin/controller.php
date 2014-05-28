<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once(__DIR__."/../include.php");
require_once(__DIR__."/../prolog.php");

if (!$USER->isAdmin()) {
    return ;
}

$request = $_REQUEST;
$fAction = function ($file) {
    global
        $USER, $DB, $APPLICATION;
    include $file;
};

$action = __DIR__.'/'.$request['q'].'.php';
if (file_exists($action)) {
    $fAction($action);
} else {
    /* @var $APPLICATION CMain */
    $APPLICATION->ThrowException("Action `$action` not exists");
}
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_after.php");
?>