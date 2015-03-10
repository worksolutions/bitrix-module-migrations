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

if (!CModule::includeModule('ws.migrations')) {
    return;
}
try {
    $options = \WS\Migrations\Module::getOptions();
    $options->enableSubjectHandler(\WS\Migrations\SubjectHandlers\IblockHandler::className());
} catch (Exception $e) {
    $fAddErrorMessage($e->getMessage());
}
