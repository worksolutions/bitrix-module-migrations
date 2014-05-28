<?php
global $USER;
if (!$USER->isAdmin()) {
    return array();
}
$inputUri = '/bitrix/admin/ws_migrations.php?q=';
return array(
    array(
        'parent_menu' => 'global_menu_store',
        'sort' => 500,
        'text' => 'Миграции данных',
        'title' => 'Миграции данных',
        'module_id' => 'ws.migrations',
        'icon' => '',
        'items_id' => 'ws_migrations_menu',
        'url' => $inputUri.'main'
    )
);
