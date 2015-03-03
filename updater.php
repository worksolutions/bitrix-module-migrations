<?php
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

$updater->Query('ALTER TABLE `ws_migrations_apply_changes_log` ADD `SOURCE` VARCHAR(50) NOT NULL AFTER `GROUP_LABEL`');

$migrateCatalog = $_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'migrations';

if (is_dir($migrateCatalog)) {
    /** @var CDBResult $res */
    $res = $updater->Query("select * from ws_migrations_apply_changes_log WHERE SOURCE IS NULL or SOURCE = ''");
    while($resItem = $res->Fetch()) {
        $fileName = $resItem['GROUP_LABEL'];
        $filePath = $migrateCatalog . DIRECTORY_SEPARATOR . $fileName;
        if (!file_exists($filePath)) {
            $fAddErrorMessage("Path `$filePath` not exists");
            continue;
        }
        $data = json_decode(file_get_contents($filePath), true);
        if (!$data[0]['version']) {
            $fAddErrorMessage("Versions by `$filePath` not exists");
            continue;
        }
        $version = $data[0]['version'];
        $update = $updater->Query('update ws_migrations_apply_changes_log set SOURCE = "'.$version.'"');
        if (!$update) {
            $fAddErrorMessage("Not update version `{$version}` into DB by file `$fileName`");
        }
    }
} else {
    $fAddErrorMessage("Migration catalog was not founded");
}