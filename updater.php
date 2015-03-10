<?php
/** @var CMain $APPLICATION */
/** @var CUser $USER */
/** @var CDatabase $DB */
/** @var CUpdater $updater */
$updater;
/**
 * Error message for processing update
 * @var string $errorMessage
 */
$fAddErrorMessage = function ($mess) use ($updater){
    $updater->errorMessage[] = $mess;
};

//=====================================================
$handlers = unserialize(COption::GetOptionString('ws.migrations', 'enabledSubjectHandlers'));
$handlers = array_diff($handlers, array('ws\migrations\SubjectHandlers\IblockHandler'));
$handlers[] = 'WS\Migrations\SubjectHandlers\IblockHandler';
COption::SetOptionString('ws.migrations', 'enabledSubjectHandlers', serialize($handlers));