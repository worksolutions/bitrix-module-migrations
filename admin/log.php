<?php

use WS\Migrations\Entities\AppliedChangesLogModel;

/** @var $localization \WS\Migrations\Localization */
$localization;

/** @var CMain $APPLICATION */
$APPLICATION->SetTitle($localization->getDataByPath('title'));
$sTableID = "ws_migrations_log_table";
$oSort = new CAdminSorting($sTableID, "date", "asc");
$lAdmin = new CAdminList($sTableID, $oSort);

$arHeaders = array(
    array("id" => "updateDate", "content" => $localization->getDataByPath('fields.updateDate'), "default"=>true),
    array("id" => "description", "content" => $localization->getDataByPath('fields.description'), "default" => true),
    array("id" => "source", "content" => $localization->getDataByPath('fields.source'), "default" => true),
    array("id" => "dispatcher", "content" => $localization->getDataByPath('fields.dispatcher'), "default" => true)
);
$lAdmin->AddHeaders($arHeaders);

$models = AppliedChangesLogModel::find(array(
    'limit' => 500
));

$rowsData = array();
array_walk($models, function (AppliedChangesLogModel $model) use (& $rowsData) {
    $row = & $rowsData[$model->groupLabel];
    if(!$row) {
        $row = array(
            'label' => $model->groupLabel,
            'updateDate' => $model->date->format('d.m.Y H:i:s'),
            'source' => $model->source,
            'dispatcher' => $model->getSetupLog()->shortUserInfo()
        );
    }
    if (in_array($model->description, array('Insert reference', 'References updates'))) {
        return;
    }
    $row['description'] = $row['description'] ? implode("<br />", array($row['description'], $model->description)) : $model->description;
});

$rsData = new CAdminResult(null, $sTableID);
$rsData->InitFromArray($rowsData);
$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint($localization->getDataByPath('messages.pages')));
while ($rowData = $rsData->NavNext()) {
    $row = $lAdmin->AddRow($rowData['label'], $rowData);
    $row->AddViewField('description', $rowData['description']);
    $row->AddActions(array(
        array(
            "ICON" => "view",
            "TEXT" => $localization->message('messages.view'),
            "ACTION" => $lAdmin->ActionRedirect("ws_migrations.php?q=detail&label=".$rowData['label']),
            "DEFAULT" => true
        )
    ));
}
if ($_REQUEST["mode"] == "list") {
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_js.php");
} else {
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
}

$lAdmin->CheckListMode();
$lAdmin->DisplayList();

if ($_REQUEST["mode"] == "list")  {
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin_js.php");
} else {
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
}
