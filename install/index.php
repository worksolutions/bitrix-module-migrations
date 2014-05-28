<?
require_once __DIR__.'/../lib/module.php';
require_once __DIR__.'/../lib/localization.php';
require_once __DIR__.'/../lib/options.php';

Class ws_migrations extends CModule {
    const MODULE_ID = 'ws.migrations';
    var $MODULE_ID = 'ws.migrations';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $strError = '';

    function __construct() {
        $arModuleVersion = array();
        include(dirname(__FILE__) . "/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

        $localization = \WS\Migrations\Module::getInstance()->getLocalization('info');
        $this->MODULE_NAME = $localization->getDataByPath("name");
        $this->MODULE_DESCRIPTION = $localization->getDataByPath("description");
        $this->PARTNER_NAME = $localization->getDataByPath("partner.name");
        $this->PARTNER_URI = $localization->getDataByPath("partner.url");
    }

    function InstallDB($arParams = array()) {
        RegisterModuleDependences('main', 'OnPageStart', self::MODULE_ID, 'WS\Migrations\Module', 'listen');
        return true;
    }

    function UnInstallDB($arParams = array()) {
        UnRegisterModuleDependences('main', 'OnPageStart', self::MODULE_ID, 'WS\Migrations\Module', 'listen');
        return true;
    }

    function InstallFiles() {
        $rootDir = \Bitrix\Main\Application::getPersonalRoot();
        $adminGatewayFile = '/admin/ws_migrations.php';
        copy(__DIR__. $adminGatewayFile, $rootDir . $adminGatewayFile);
        return true;
    }

    function UnInstallFiles() {
        $rootDir = \Bitrix\Main\Application::getPersonalRoot();
        $adminGatewayFile = '/admin/ws_migrations.php';
        unlink($rootDir . $adminGatewayFile);
        return true;
    }

    function DoInstall() {
        $this->InstallFiles();
        $this->InstallDB();
        RegisterModule(self::MODULE_ID);
    }

    function DoUninstall() {
        UnRegisterModule(self::MODULE_ID);
        $this->UnInstallDB();
        $this->UnInstallFiles();
    }
}

