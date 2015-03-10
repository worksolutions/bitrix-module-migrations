<?
include __DIR__.'/prolog.php';
$module = \WS\Migrations\Module::getInstance();
$localization = $module->getLocalization('setup');
$options = $module->getOptions();

if ($data = $_POST['data']) {
    $data['catalog'] && $options->catalogPath = $data['catalog'];
    $options->useAutotests = (bool)$data['tests'];

    foreach ($module->getSubjectHandlers() as $handler) {
        $handlerClass = get_class($handler);

        $handlerClassValue = (bool)$data['handlers'][$handlerClass];
        $handlerClassValue && $module->enableSubjectHandler($handlerClass);
        !$handlerClassValue && $module->disableSubjectHandler($handlerClass);
    }
}

$form = new CAdminForm('form', array(
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
$form->AddCheckBoxField('data[tests]', $localization->getDataByPath('fields.useAutotests'), true, '1', (bool)$options->useAutotests);
$form->AddSection('disableHandlers', $localization->getDataByPath('section.disableHandlers'));

foreach ($module->getSubjectHandlers() as $handler) {
    $form->AddCheckBoxField('data[handlers]['.get_class($handler).']', $handler->getName(), true, '1', $options->isEnableSubjectHandler(get_class($handler)));
}

$form->Buttons(array('btnSave' => false, 'btnÀpply' => true));
$form->Show();