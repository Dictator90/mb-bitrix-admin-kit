<?php

declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/vendor.demo/include.php';

use Vendor\Demo\Admin\DashboardPage;
use Vendor\Demo\Admin\ProductResource;
use Vendor\Demo\Admin\SettingsPage;

return [[
    'parent_menu' => 'global_menu_content',
    'section' => 'vendor_demo',
    'sort' => 200,
    'text' => 'AdminKit demo',
    'title' => 'AdminKit demo',
    'url' => 'demo_admin.php?page=' . DashboardPage::getId(),
    'icon' => DashboardPage::getMenuIcon(),
    'items_id' => 'menu_vendor_demo',
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
]];
