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

// install file platform version
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . \COption::GetOptionString("main", "upload_dir", "upload");
$modulePath = $_SERVER['DOCUMENT_ROOT'] .$updater->curModulePath;

if (!is_dir($uploadDir.'/ws.migrations')) {
    $copyRes = CopyDirFiles($modulePath.'/install/upload', $uploadDir, false, true);
    if (!$copyRes) {
        $fAddErrorMessage("Use platform version catalog not setup");
        return;
    }
    // init file version
    $versionRaw = \COption::GetOptionString($updater->moduleID, 'version');
    $version = unserialize($versionRaw);

    $ownerRaw = \COption::GetOptionString($updater->moduleID, 'owner');
    $owner = unserialize($ownerRaw);

    $createVersionFile = file_put_contents(
        $uploadDir.'/ws.migrations/version.dat',
        $version.':#:'.md5($this->version.$modulePath.'/lib/platformversion.php').':#:'.$owner
    );
    if (!$createVersionFile) {
        $fAddErrorMessage("Version file was not created");
        return;
    }
}

// cli
$dest = $_SERVER['DOCUMENT_ROOT'].'bitrix/tools';
$copyRes = CopyDirFiles($_SERVER['DOCUMENT_ROOT'] .$updater->curModulePath.'/install/tools', $dest, false, true);
if (!$copyRes) {
    $fAddErrorMessage("Cli interface file was not created");
    return;
}
