<?php
global $APPLICATION, $errors;
$localization = \WS\Migrations\Module::getInstance()->getLocalization('uninstall');
$options = \WS\Migrations\Module::getInstance()->getOptions();
$form = new CAdminForm('ew', array(
    array(
        'DIV' => 't1',
        'TAB' => $localization->getDataByPath('tab'),
    )
));
ShowMessage(array(
    'MESSAGE' => $localization->getDataByPath('description'),
    'TYPE' => 'OK'
));

$errors && ShowError(implode(', ', $errors));
$form->Begin(array(
    'FORM_ACTION' => $APPLICATION->GetCurUri()
));
$form->BeginNextFormTab();
$form->AddCheckBoxField('data[removeAll]', $localization->getDataByPath('fields.removeAll'), true, "Y", false);
$form->Buttons(array('btnSave' => false, 'btnÀpply' => true));
$form->Show();