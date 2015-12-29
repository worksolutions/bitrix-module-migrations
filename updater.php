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

$docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/').'/';
// install file platform version
$uploadDir = $docRoot . \COption::GetOptionString("main", "upload_dir", "upload");
$modulePath = rtrim($docRoot, '/').$updater->kernelPath.'/modules/'.$updater->moduleID;
$updatePath = $docRoot.$updater->curModulePath;

$isInstalled = \Bitrix\Main\ModuleManager::isModuleInstalled($updater->moduleID);
if (!$isInstalled) {
    return;
}

if (!is_dir($uploadDir.'/ws.migrations')) {
    $copyRes = CopyDirFiles($updatePath.'/install/upload', $uploadDir, false, true);
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
        $version.':#:'.md5($version.$modulePath.'/lib/platformversion.php').':#:'.$owner
    );
    if (!$createVersionFile) {
        $fAddErrorMessage("Version file was not created");
        return;
    }
}

// cli
$dest = $docRoot.'bitrix/tools';
$copyRes = CopyDirFiles($docRoot .$updater->curModulePath.'/install/tools', $dest, false, true);
if (!$copyRes) {
    $fAddErrorMessage("Cli interface file was not created");
    return;
}
