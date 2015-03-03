<?php
$apply = false;
if ($_POST['rollback']) {
    \WS\Migrations\Module::getInstance()->rollbackLastChanges();
    $apply = true;
}

if ($_POST['apply']) {
    \WS\Migrations\Module::getInstance()->applyNewFixes();
    $apply = true;
}
$apply && LocalRedirect($APPLICATION->GetCurUri());

$fixes = array();
$notAppliedFixes = \WS\Migrations\Module::getInstance()->getNotAppliedFixes();
foreach ($notAppliedFixes as $fix) {
    $fixes[$fix->getName()]++;
}
$lastSetupLog = \WS\Migrations\Module::getInstance()->getLastSetupLog();

if ($lastSetupLog) {
    $appliedFixes = array();
    $errorFixes = array();

    foreach ($lastSetupLog->getAppliedLogs() as $appliedLog) {
        !$appliedLog->success && $errorFixes[$appliedLog->description]++;
        $appliedLog->success && $appliedFixes[$appliedLog->description]++;
    }
}

//--------------------------------------------------------------------------

/** @var $localization \WS\Migrations\Localization */
/** @var $APPLICATION CMain */
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
$localization;
$module = \WS\Migrations\Module::getInstance();
?><form method="POST" action="<?=
$APPLICATION->GetCurUri()?>" ENCTYPE="multipart/form-data" name="apply"><?
$form = new CAdminForm('ws_maigrations_main', array(
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
    <td width="60%">
        <b><?= $module->getDbVersion()." [".$module->getVersionOwner()."]"?></b><br />
        <a href="/bitrix/admin/ws_migrations.php?q=changeversion"><?=$localization->getDataByPath('change_link')?></a>
    </td>
</tr><?
$form->EndCustomField('version');
$form->BeginCustomField('list', 'vv');
?>
<tr style="color: #ff8000;">
    <td width="30%"><?=$localization->getDataByPath('list')?>:</td>
    <td width="60%">
<?if($fixes):?>
        <ul>
<?foreach ($fixes as $fixName => $fixCount):?>
            <li><b><?=$fixName?></b> [<b><?=$fixCount?></b>]</li>
<?endforeach;?>
        </ul>
        <a href="#" id="newChangesViewLink"><?=$localization->getDataByPath('newChangesDetail')?></a>
<?else:?>
<b><?=$localization->message('common.listEmpty')?></b>
<?endif;?>
    </td>
</tr>
<?
$form->EndCustomField('list');
//--------------------
if ($lastSetupLog) {
    $form->AddSection('lastSetup', $localization->message('lastSetup.sectionName', array(
        ':time:' => $lastSetupLog->date->format('d.m.Y H:i:s'),
        ':user:' => $lastSetupLog->shortUserInfo()
    )));
    $form->BeginCustomField('appliedList', 'vv');
    ?>
    <tr style="color: #32cd32;">
        <td width="30%"><?=$localization->getDataByPath('appliedList')?>:</td>
        <td width="60%">
    <?if($appliedFixes):?>
            <ul>
    <?foreach ($appliedFixes as $fixName => $fixCount):?>
                <li><b><?=$fixName?></b> [<b><?=$fixCount?></b>]</li>
    <?endforeach;?>
            </ul>
    <?else:?>
    <b><?=$localization->message('common.listEmpty')?></b>
    <?endif;?>
        </td>
    </tr>
    <?
    $form->EndCustomField('appliedList');
    //--------------------
    $form->BeginCustomField('errorList', 'vv');
    ?>
    <tr style="color: #ff0000;">
        <td width="30%"><?=$localization->getDataByPath('errorList')?>:</td>
        <td width="60%">
    <?if($errorFixes):?>
            <ul>
    <?foreach ($errorFixes as $fixName => $fixCount):?>
                <li><b><?=$fixName?></b> [<b><?=$fixCount?></b>]</li>
    <?endforeach;?>
            </ul>
    <?else:?>
    <b><?=$localization->message('common.listEmpty')?></b>
    <?endif;?>
        </td>
    </tr>
    <?
    $form->EndCustomField('errorList');
}
$form->EndTab();
!$fixes && !$lastSetupLog && $form->bPublicMode = true;
$form->Buttons(array('btnSave' => false, 'btnÀpply' => true));
$lastSetupLog
    && $form->sButtonsContent = '<input type="submit" name="rollback" value="'.$localization->getDataByPath('btnRollback').'" title="'.$localization->getDataByPath('btnRollback').'"/>';

$form->Show();
?></form>
<script type="text/javascript">
    $(function () {
        var $chLink = $(document.getElementById('newChangesViewLink'));
        $chLink.click(function (e) {e.preventDefault();});

        $chLink.on("click", function () {
            (new BX.CDialog({
                'title': "<?=$localization->message("newChangesTitle")?>",
                'content_url': '/bitrix/admin/ws_migrations.php?q=newChangesList',
                'width': 900,
                'buttons': [BX.CAdminDialog.btnClose],
                'resizable': false
            })).Show();
        });
    });
</script>