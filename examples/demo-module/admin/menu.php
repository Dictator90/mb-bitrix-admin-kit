<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\Manager\AdminKitManager;
use MB\Bitrix\AdminKit\Manager\AdminKitScope;
use Vendor\Demo\Admin\DashboardPage;
use Vendor\Demo\Admin\ProductResource;
use Vendor\Demo\Admin\SettingsPage;

Loader::includeModule('demo.module');

$scope = AdminKitScope::fromModuleId('demo.module');

return [
    'parent_menu' => 'global_menu_content',
    'section' => 'vendor_demo',
    'sort' => 200,
    'text' => 'AdminKit demo',
    'title' => 'AdminKit demo',
    'url' => 'demo_admin.php?page=' . DashboardPage::getId(),
    'icon' => DashboardPage::getMenuIcon(),
    'items_id' => 'menu_vendor_demo',
    'items' => (new AdminKitManager($scope))->getMenu(),
    // Or manually
    /*
    'items' => [
        [
            'text' => 'Dashboard',
            'title' => DashboardPage::getTitle(),
            'url' => 'demo_admin.php?page=' . DashboardPage::getId(),
            'sort' => DashboardPage::getSort(),
        ],
        [
            'text' => 'Products',
            'title' => 'Demo products',
            'url' => 'demo_admin.php?page=' . ProductResource::getId(),
            'sort' => ProductResource::getSort(),
        ],
        [
            'text' => 'Settings',
            'title' => SettingsPage::getTitle(),
            'url' => 'demo_admin.php?page=' . SettingsPage::getId(),
            'sort' => SettingsPage::getSort(),
        ],
    ],
    */
];
