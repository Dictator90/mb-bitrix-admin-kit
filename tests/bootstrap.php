<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use MB\BitrixTest\Bootstrap\PrologBootstrap;
use MB\BitrixTest\Install\InstalledCore;

if (!defined('B_PROLOG_INCLUDED')) {
    define('B_PROLOG_INCLUDED', true);
}

putenv('BITRIX_RUNTIME_ROOT=' . __DIR__ . '/.runtime/integration');
putenv('BITRIX_SQLITE_MODE=shop');
putenv('BITRIX_SQLITE_IMPORT_CORE_INSTALL_SQL=1');
putenv('BITRIX_SQLITE_IMPORT_CORE_SHOP_DEMO_SQL=1');
putenv('BITRIX_IMPORT_ESHOP_DEMO_XML=1');
putenv('BITRIX_ESHOP_LOCALIZATION=ru');

$corePath = InstalledCore::path();
if (!is_dir($corePath . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'main')) {
    throw new RuntimeException('Bitrix core is not installed or incomplete at: ' . $corePath);
}

PrologBootstrap::reset();
PrologBootstrap::boot([
    'core_path' => $corePath,
    'runtime_root' => __DIR__ . '/.runtime/integration',
    'sqlite' => true,
]);

require_once __DIR__ . '/bootstrap-integration.php';
