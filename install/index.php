<?
use Bitrix\Main\Application;

require_once __DIR__.'/../lib/Module.php';
require_once __DIR__.'/../lib/Localization.php';
require_once __DIR__.'/../lib/Options.php';
require_once __DIR__.'/../lib/ModuleOptions.php';

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
        RegisterModuleDependences('main', 'OnAfterEpilog', self::MODULE_ID, 'WS\Migrations\Module', 'commitDutyChanges');
        global $DB;
        $DB->RunSQLBatch(Application::getDocumentRoot().'/'.Application::getPersonalRoot() . "/modules/".$this->MODULE_ID."/install/db/install.sql");
        return true;
    }

    function UnInstallDB($arParams = array()) {
        UnRegisterModuleDependences('main', 'OnPageStart', self::MODULE_ID, 'WS\Migrations\Module', 'listen');
        UnRegisterModuleDependences('main', 'OnAfterEpilog', self::MODULE_ID, 'WS\Migrations\Module', 'commitDutyChanges');
        global $DB;
        $DB->RunSQLBatch(Application::getDocumentRoot().'/'.Application::getPersonalRoot()."/modules/".$this->MODULE_ID."/install/db/uninstall.sql");
        return true;
    }

    function InstallFiles() {
        $rootDir = Application::getDocumentRoot().'/'.Application::getPersonalRoot();
        $adminGatewayFile = '/admin/ws_migrations.php';
        copy(__DIR__. $adminGatewayFile, $rootDir . $adminGatewayFile);
        return true;
    }

    function UnInstallFiles() {
        $rootDir = Application::getDocumentRoot().'/'.Application::getPersonalRoot();
        $adminGatewayFile = '/admin/ws_migrations.php';
        unlink($rootDir . $adminGatewayFile);
        return true;
    }

    function DoInstall() {
        global $APPLICATION, $data;
        $loc = \WS\Migrations\Module::getInstance()->getLocalization('setup');
        $options = \WS\Migrations\Module::getInstance()->getOptions();
        global $errors;
        $errors = array();
        if ($data['catalog']) {
            $dir = $_SERVER['DOCUMENT_ROOT'].$data['catalog'];
            if (!is_dir($dir)) {
                mkdir($dir);
            }
            if (!is_dir($dir)) {
                $errors[] = $loc->getDataByPath('error.notCreateDir');
            }
            if (!$errors) {
                $options->catalogPath = $data['catalog'];
            }
            \WS\Migrations\Module::getInstance()->install();
        }
        if (!$data || $errors) {
            $APPLICATION->IncludeAdminFile($loc->getDataByPath('title'), __DIR__.'/form.php');
            return;
        }
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

