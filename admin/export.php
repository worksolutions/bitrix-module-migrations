<?php
/** @var $APPLICATION CMain */

/** @var \WS\Migrations\PlatformVersion $platformVersion */
$platformVersion = \WS\Migrations\Module::getInstance()->getPlatformVersion();

if ($_POST['apply']) {
    $text = \WS\Migrations\Module::getInstance()->getExportText();
    $APPLICATION->RestartBuffer();
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"".$platformVersion->getValue().".json\"");
    header("Accept-Ranges: bytes");
    header("Cache-Control: must-revalidate");
    header("Expires: 0");
    header("Pragma: public");
    echo $text;
    die;
}

/** @var $localization \WS\Migrations\Localization */
$localization;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?><form method="POST" action="<?=
$APPLICATION->GetCurUri()?>" ENCTYPE="multipart/form-data" name="apply"><?
    $form = new CAdminForm('ws_maigrations_export', array(
        array(
            "DIV" => "edit1",
            "TAB" => $localization->getDataByPath('title'),
            "ICON" => "iblock",
            "TITLE" => $localization->getDataByPath('title'),
        ) ,
    ));

    $form->Begin(array(
        'FORM_ACTION' => $APPLICATION->GetCurUri()
    ));
    $form->BeginNextFormTab();
    $form->BeginCustomField('version', 'vv');
    ?><tr>
        <td width="30%"><?=$localization->getDataByPath('version')?>:</td>
        <td width="60%"><b><?=$platformVersion->getValue()?></b></td>
    </tr><?
    $form->EndCustomField('version');
    $form->EndTab();
    $form->Buttons(array('btnSave' => false, 'btnÀpply' => true));
    $form->Show();
    ?></form><?
