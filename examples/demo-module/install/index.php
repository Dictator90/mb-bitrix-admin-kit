<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\ModuleManager;

class vendor_demo extends CModule
{
    public $MODULE_ID = 'vendor.demo';
    public $MODULE_NAME = 'AdminKit demo module';
    public $MODULE_DESCRIPTION = 'Demo module for mb4it/bitrix-admin-kit v0.9.0';
    public $PARTNER_NAME = 'MB4IT';
    public $PARTNER_URI = 'https://github.com/mb4it';

    public function DoInstall(): void
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDb();
    }

    public function DoUninstall(): void
    {
        $this->uninstallDb();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function installDb(): void
    {
        $connection = Application::getConnection();
        if (!$connection->isTableExists('vendor_demo_product')) {
            $connection->queryExecute(
                "CREATE TABLE vendor_demo_product (
                    ID int(11) NOT NULL AUTO_INCREMENT,
                    NAME varchar(255) NOT NULL,
                    TYPE varchar(32) NOT NULL DEFAULT 'simple',
                    ACTIVE char(1) NOT NULL DEFAULT 'Y',
                    SORT int(11) NOT NULL DEFAULT 500,
                    PRICE decimal(12,2) NULL,
                    CREATED_BY int(11) NULL,
                    PRIMARY KEY (ID)
                )"
            );
        }

        if (!$connection->isTableExists('vendor_demo_settings')) {
            $connection->queryExecute(
                "CREATE TABLE vendor_demo_settings (
                    ID int(11) NOT NULL AUTO_INCREMENT,
                    CODE varchar(100) NOT NULL,
                    NAME varchar(255) NOT NULL,
                    SCOPE varchar(32) NOT NULL DEFAULT 'general',
                    VALUE text NULL,
                    ACTIVE char(1) NOT NULL DEFAULT 'Y',
                    SORT int(11) NOT NULL DEFAULT 500,
                    PRIMARY KEY (ID),
                    UNIQUE KEY UX_VENDOR_DEMO_SETTINGS_CODE (CODE)
                )"
            );
        }
    }

    public function uninstallDb(): void
    {
        Application::getConnection()->queryExecute('DROP TABLE IF EXISTS vendor_demo_product');
        Application::getConnection()->queryExecute('DROP TABLE IF EXISTS vendor_demo_settings');
    }
}
