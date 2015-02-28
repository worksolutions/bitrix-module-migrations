<?php
/** @var CUpdater $updater */
$updater;
/**
 * Error message for processing update
 * @var string $errorMessage
 */
$errorMessage = null;

//=====================================================

$updater->Query('ALTER TABLE `ws_migrations_apply_changes_log` ADD `SOURCE` VARCHAR(50) NOT NULL AFTER `GROUP_LABEL`');