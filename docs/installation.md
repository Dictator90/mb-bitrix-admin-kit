# Installation

## Requirements

- PHP `^8.2`.
- 1C-Bitrix with the D7 ORM and the admin area available.
- Composer autoload enabled in the Bitrix module or application.

## Composer

```bash
composer require mb4it/bitrix-admin-kit
```

The package requires `mb4it/collections`, `mb4it/stringable`, `mb4it/conditionable`, and `mb4it/filesystem` through Composer. AdminKit wraps support behavior with `AdminCollection`, `AdminString`, `AdminCondition`, and `Discovery\ClassDiscovery`; module code can keep using plain PHP arrays, strings, booleans, callables, and regular PHP classes while class discovery is delegated to `MB\Filesystem\Finder\ClassFinder`.

## Bitrix module bootstrap

Load Composer from the module `include.php` before rendering admin pages:

```php
<?php

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
```

AdminKit does not register global helper functions and does not create automatic `class_alias()` mappings, which keeps it safe for projects where support packages are already installed.

## Development checks

```bash
composer validate --strict
composer test
composer analyse
composer cs-check
```
