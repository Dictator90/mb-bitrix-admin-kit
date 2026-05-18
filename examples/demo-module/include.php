<?php

declare(strict_types=1);

$vendorAutoload = __DIR__ . '/vendor/autoload.php';
$projectAutoload = dirname(__DIR__, 3) . '/vendor/autoload.php';

if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
} elseif (is_file($projectAutoload)) {
    require_once $projectAutoload;
}
