<?php
global $APPLICATION, $errors;
$localization = \WS\Migrations\Module::getInstance()->getLocalization('setup');
$options = \WS\Migrations\Module::getInstance()->getOptions();
$form = new CAdminForm('ew', array(
    array(
        'DIV' => 't1',
        'TAB' => $localization->getDataByPath('tab'),
    )
));
echo BeginNote();
echo $localization->getDataByPath('description');
echo EndNote();
$errors && ShowError(implode(', ', $errors));
$form->Begin(array(
    'FORM_ACTION' => $APPLICATION->GetCurUri()
));
$form->BeginNextFormTab();
$form->AddEditField('data[catalog]', $localization->getDataByPath('fields.catalog'), true, array(), $options->catalogPath ?: '/migrations');
$form->Buttons(array('btnSave' => false, 'btnÀpply' => true));
$form->Show();