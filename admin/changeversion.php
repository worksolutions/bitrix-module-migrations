<?php
if ($_POST['changeversion']) {
     \WS\Migrations\Module::getInstance()->runRefreshVersion();
}

/** @var $localization \WS\Migrations\Localization */
$localization;

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?><form id="ws_maigrations_import" method="POST" action="<?=
$APPLICATION->GetCurUri()?>" ENCTYPE="multipart/form-data" name="apply"><?
$form = new CAdminForm('ws_maigrations_import', array(
    array(
        "DIV" => "edit1",
        "TAB" => $localization->getDataByPath('title'),
        "ICON" => "iblock",
        "TITLE" => $localization->getDataByPath('title'),
    ) ,
));
$form->BeginPrologContent();
ShowNote($localization->getDataByPath('description'));
$form->EndPrologContent();
$form->Begin(array(
    'FORM_ACTION' => $APPLICATION->GetCurUri()
));
$form->BeginNextFormTab();
$form->BeginCustomField('version', 'vv');
?><tr>
    <td width="30%"><?=$localization->getDataByPath('version')?>:</td>
    <td width="60%"><b><?=\WS\Migrations\Module::getInstance()->getDbVersion()?></b></td>
    </tr>
    <tr>
        <td></td>
        <td ><input type="submit" name="changeversion" value="<?=$localization->getDataByPath('button_change')?>"></td>
    </tr><?
$form->EndCustomField('version');
$form->EndTab();
//$form->Buttons(array('btnSave' => false, 'btnÀpply' => true));
$form->Show();
?>
</form>
