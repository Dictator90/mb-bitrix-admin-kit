<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\Manager\AdminKitManager;
use MB\Bitrix\AdminKit\Manager\AdminKitScope;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loader::includeModule('vendor.demo');

global $APPLICATION, $adminPage;
$adminPage->hideTitle();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$scope = AdminKitScope::fromModuleId('vendor.demo');
(new AdminKitManager($scope))->getCurrentPage()->render();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
