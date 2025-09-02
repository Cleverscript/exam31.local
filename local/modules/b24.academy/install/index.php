<?php
B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

class b24_academy extends CModule
{
    var $MODULE_ID = 'b24.academy';

    protected string $fileRoot;

    public static function getModuleId()
    {
        return basename(dirname(__DIR__));
    }

    public function __construct()
    {
        $arModuleVersion = array();
        include(dirname(__FILE__) . '/version.php');
        $this->MODULE_ID = self::getModuleId();
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('B24_ACADEMY_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('B24_ACADEMY_MODULE_DESC');
        $this->PARTNER_NAME = Loc::getMessage('B24_ACADEMY_NAME');
        $this->PARTNER_URI = Loc::getMessage('B24_ACADEMY_URI');

        $this->fileRoot = Application::getDocumentRoot();
    }

    public function DoInstall()
    {
        try {
            ModuleManager::registerModule($this->MODULE_ID);
            $this->InstallDB();

        } catch (\Throwable $t) {
            global $APPLICATION;
            $APPLICATION->throwException($t->getMessage());

            return false;
        }

        return true;
    }

    public function DoUninstall()
    {
        try {
            $this->UnInstallDB();

            ModuleManager::unRegisterModule($this->MODULE_ID);
        } catch (\Throwable $t) {
            global $APPLICATION;
            $APPLICATION->throwException($t->getMessage());

            return false;
        }

        return true;
    }

    public function InstallDB(): bool
    {
        if (!Loader::includeModule($this->MODULE_ID)) {
            return false;
        }

        return true;
    }

    public function UnInstallDB(): bool
    {
        if (!Loader::includeModule($this->MODULE_ID)) {
            return false;
        }

        return true;
    }
}
