<?php

declare(strict_types=1);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/vendor.demo/include.php';

use Vendor\Demo\Admin\DashboardPage;
use Vendor\Demo\Admin\ProductResource;
use Vendor\Demo\Admin\SettingsPage;

$page = (string)($_REQUEST['page'] ?? ProductResource::getId());
$action = (string)($_REQUEST['action'] ?? 'index');
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : null;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

if ($page === SettingsPage::getId()) {
    echo (new SettingsPage())->render();
} elseif ($page === DashboardPage::getId()) {
    echo (new DashboardPage())->render();
} else {
    $resource = new ProductResource();

    match ($action) {
        'add' => $resource->formPage()->render(),
        'edit' => $resource->formPage($id)->render(),
        'detail', 'view' => $resource->detailPage($id)->render(),
        default => $resource->indexPage()->render(),
    };
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
