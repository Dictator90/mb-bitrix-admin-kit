# Installation

## Requirements

- PHP `^8.2`.
- 1C-Bitrix with the D7 ORM and the admin area available.
- Composer autoload enabled in the Bitrix module or application.

## Composer

```bash
composer require mb4it/bitrix-admin-kit
```

The package requires `mb4it/collections`, `mb4it/stringable`, and `mb4it/conditionable` through Composer. AdminKit wraps them with `AdminCollection`, `AdminString`, and `AdminCondition`, so module code can keep using plain PHP arrays, strings, booleans, and callables.

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
