<?php
/** @var $APPLICATION CMain */

/** @var $localization \WS\Migrations\Localization */
$localization;
$module = \WS\Migrations\Module::getInstance();

$apply = false;
if ($_POST['rollback']) {
    $module->rollbackLastChanges();
    $apply = true;
}

if ($_POST['apply']) {
    $module->applyNewFixes();
    $apply = true;
}
$apply && LocalRedirect($APPLICATION->GetCurUri());

$fixes = array();
$notAppliedFixes = $module->getNotAppliedFixes();
foreach ($notAppliedFixes as $fix) {
    $fixes[$fix->getName()]++;
}
$scenarios = array();
foreach ($module->getNotAppliedScenarios() as $notAppliedScenarioClassName) {
    $scenarios[] = $notAppliedScenarioClassName::name();
}

$lastSetupLog = \WS\Migrations\Module::getInstance()->getLastSetupLog();
if ($lastSetupLog) {
    $appliedFixes = array();
    $errorFixes = array();

    foreach ($lastSetupLog->getAppliedLogs() as $appliedLog) {
        !$appliedLog->success && $errorFixes[] = $appliedLog;
        $appliedLog->success && $appliedFixes[$appliedLog->description]++;
    }
}

//--------------------------------------------------------------------------
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
// 1C-Bitrix override variable!!
$module = \WS\Migrations\Module::getInstance();

?><form method="POST" action="<?=$APPLICATION->GetCurUri()?>" ENCTYPE="multipart/form-data" name="apply"><?
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
$versionColor = "green";
!$module->isValidVersion() && $versionColor = "red";
?><tr>
    <td width="30%"><?=$localization->getDataByPath('version')?>:</td>
    <td width="60%">
        <b style="color: <?=$versionColor?>;"><?= $module->getDbVersion()." [".$module->getVersionOwner()."]"?></b><br />
        <a href="/bitrix/admin/ws_migrations.php?q=changeversion"><?=$localization->getDataByPath('change_link')?></a>
    </td>
</tr><?
$form->EndCustomField('version');
$form->BeginCustomField('list', 'vv');
?>
<tr style="color: #ff8000;">
    <td width="30%"><?=$localization->getDataByPath('list.auto')?>:</td>
    <td width="60%">
<?if($fixes):?>
        <ul>
<?foreach ($fixes as $fixName => $fixCount):?>
            <li><b><?=$fixName?></b> [<b><?=$fixCount?></b>]</li>
<?endforeach;?>
            <li><a href="#" id="newChangesViewLink"><?=$localization->getDataByPath('newChangesDetail')?></a></li>
        </ul>
<?else:?>
<b><?=$localization->message('common.listEmpty')?></b>
<?endif;?>
    </td>
</tr>
    <tr style="color: #ff8000;">
        <td width="30%"><?=$localization->getDataByPath('list.scenarios')?>:</td>
        <td width="60%">
<?if($scenarios):?>
        <ul>
<?foreach ($scenarios as $scenario):?>
            <li><b><?=$scenario?></b></li>
<?endforeach;?>
        </ul>
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
    <?php
    /** @var \WS\Migrations\Entities\AppliedChangesLogModel $errorApply */
    foreach ($errorFixes as $errorApply):
        $errorData = \WS\Migrations\jsonToArray($errorApply->description) ?: $errorApply->description;
        if (is_scalar($errorData)) {
            ?><li><b><?=$errorData?></b></li><?
        }
        if (is_array($errorData)) {
            ?><li><b><a href="#" class="apply-error-link" data-id="<?=$errorApply->id?>"><?=$errorData['message']?></a></b></li><?
        }
    ?>
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
        var $applyErrorLinks = $('a.apply-error-link');
        $($chLink, $applyErrorLinks).click(function (e) {e.preventDefault();});

        $chLink.on("click", function () {
            (new BX.CDialog({
                'title': "<?=$localization->message("newChangesTitle")?>",
                'content_url': '/bitrix/admin/ws_migrations.php?q=newChangesList',
                'width': 900,
                'buttons': [BX.CAdminDialog.btnClose],
                'resizable': false
            })).Show();
        });

        $applyErrorLinks.on("click", function () {
            var id = $(this).data('id');
            (new BX.CDialog({
                'title': "<?=$localization->message("errorWindow")?>",
                'content_url': '/bitrix/admin/ws_migrations.php?q=applyError&id='+id,
                'width': 900,
                'buttons': [BX.CAdminDialog.btnClose],
                'resizable': false
            })).Show();
        });
    });
</script>