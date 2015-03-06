<?php

/** @var $APPLICATION CMain */
/** @var $localization \WS\Migrations\Localization */
$localization;
$module = \WS\Migrations\Module::getInstance();

if ($_POST['apply'] != "" && $_POST['name']) {
    $name = $_POST['name'];
    $description = $_POST['description'];

    // создание класса
    $templateContent = file_get_contents(__DIR__.'/../data/scenarioTemplate.tpl');
    $arReplace = array(
        '#class_name#' => $className = 'ws_m_'.time().'_'.CUtil::translit($name, LANGUAGE_ID),
        '#name#' => $name,
        '#description#' => $description,
        '#db_version#' => $module->getDbVersion(),
        '#owner#' => $module->getVersionOwner()
    );
    $classContent = str_replace(array_keys($arReplace), array_values($arReplace), $templateContent);
    $fileName = $className.'.php';
    $fileName = $module->putScriptClass($fileName, $classContent);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
// написание сообщения
$fileName && ShowNote($localization->message('path-to-file', array('#path#' => $fileName)));
?><form method="POST" action="<?=$APPLICATION->GetCurUri()?>" ENCTYPE="multipart/form-data" name="apply"><?
$form = new CAdminForm('ws_maigrations_create_scenario', array(
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
$form->AddEditField('name', $localization->message('field.name'), true, array('size' => 50));
$form->AddTextField('description', $localization->message('field.description'), '', array('cols' => 51), true);
$form->EndTab();
$form->Buttons(array('btnSave' => false, 'btnАpply' => true));
$form->Show();
?></form>
