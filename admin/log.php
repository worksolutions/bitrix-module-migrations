<?php
/** @var $localization \WS\Migrations\Localization */
$localization;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
$sTableID = "ws_migrations_log_table";
$oSort = new CAdminSorting($sTableID, "TITLE", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);
