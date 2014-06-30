<?php
if ($_POST['apply']) {
    !$_POST['isScheme'] && \WS\Migrations\Module::getInstance()->runRefreshVersion();
    if ($_POST['isScheme']) {
        $text = file_get_contents($_FILES['file']['tmp_name']);
        // Импорт данных, при выводе нужен отчет
        // Сколько создано, сколько обновлено
        \WS\Migrations\Module::getInstance()->import($text);
    }
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
    </tr><?
$form->EndCustomField('version');
$form->AddCheckBoxField('isScheme', $localization->getDataByPath('fields.isScheme'), false, 'Y', false);
$form->AddFileField('file', $localization->getDataByPath('fields.file'), '');
$form->EndTab();
$form->Buttons(array('btnSave' => false, 'btnАpply' => true));
$form->Show();
?>
</form>
<script type="text/javascript">
    $(function () {
        var $form = $('#ws_maigrations_import');
        var $fileRaw = $('input[name="file"]', $form).parents('tr:first');
        var $schemeFlag = $('input[name="isScheme"]', $form);
        var actualizeFileInput = function () {
            var isScheme = $schemeFlag.is(':checked');
            isScheme ? $fileRaw.show() : $fileRaw.hide();
        };

        $schemeFlag.change(function (e) {
            e.preventDefault();
            actualizeFileInput();
        });
        actualizeFileInput();
    });
</script>
