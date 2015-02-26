<?php
CModule::IncludeModule('ws.migrations');
define("ADMIN_MODULE_NAME", \WS\Migrations\Module::getName());
CJSCore::Init(array('window', 'jquery', 'dialog'));