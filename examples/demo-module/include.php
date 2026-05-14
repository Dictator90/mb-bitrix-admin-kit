<?php

declare(strict_types=1);

$vendorAutoload = __DIR__ . '/vendor/autoload.php';
$projectAutoload = dirname(__DIR__, 3) . '/vendor/autoload.php';

if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
} elseif (is_file($projectAutoload)) {
    require_once $projectAutoload;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'Vendor\\Demo\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = __DIR__ . '/lib/' . $relative . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
