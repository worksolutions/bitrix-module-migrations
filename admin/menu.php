<?php
global $USER;
if (!$USER->isAdmin()) {
    return array();
}
$loc = \WS\Migrations\Module::getInstance()->getLocalization('menu');
$inputUri = '/bitrix/admin/ws_migrations.php?q=';
return array(
    array(
        'parent_menu' => 'global_menu_settings',
        'sort' => 500,
        'text' => $loc->getDataByPath('title'),
        'title' => $loc->getDataByPath('title'),
        'module_id' => 'ws.migrations',
        'icon' => '',
        'items_id' => 'ws_migrations_menu',
        'items' => array(
            array(
                'text' => $loc->getDataByPath('apply'),
                'url' => $inputUri.'main',
            ),
            array(
                'text' => $loc->getDataByPath('changeversion'),
                'url' => $inputUri.'changeversion',
            ),
            array(
                'text' => $loc->getDataByPath('log'),
                'url' => $inputUri.'log',
            )
        )
    )
);
