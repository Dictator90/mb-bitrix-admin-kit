# Установка

## Требования

- PHP `^8.2`
- 1С‑Битрикс с D7 ORM и доступом к административной части
- Composer

## Composer

```bash
composer require mb4it/bitrix-admin-kit
```

Пакет подтягивает зависимости `mb4it/*` и использует их через внутренние адаптеры (`AdminCollection`, `AdminString`, `AdminCondition`, `Discovery\ClassDiscovery`). Пользовательский код может работать с обычными PHP-массивами, строками, `callable` и классами.

## Bootstrap внутри Bitrix-модуля

Зависимости можно установить как в директорию модуля, так и в корень Bitrix-проекта. В `include.php` модуля удобно поддержать оба варианта:

```php
<?php

declare(strict_types=1);

$vendorAutoload = __DIR__ . '/vendor/autoload.php';
$projectAutoload = dirname(__DIR__, 3) . '/vendor/autoload.php';

if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
} elseif (is_file($projectAutoload)) {
    require_once $projectAutoload;
}
```

В admin-файле модуль нужно подключать явно:

```php
use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\Manager\AdminKitManager;
use MB\Bitrix\AdminKit\Manager\AdminKitScope;

Loader::includeModule('vendor.demo');

$scope = AdminKitScope::fromModuleId('vendor.demo');
(new AdminKitManager($scope))->getCurrentPage()->render();
```

`Loader::includeModule('vendor.demo')` подключает модуль, его `include.php` и классы. `AdminKitScope::fromModuleId('vendor.demo')` только находит физическую директорию модуля и собирает discovery paths для `Resource`/`Page` классов.

## Bootstrap вне модуля

Для `local/admin`-страниц без отдельного Bitrix-модуля устанавливайте пакет в корне проекта:

```bash
cd <bitrix-project-root>
composer require mb4it/bitrix-admin-kit
```

Подключите Composer autoload глобально в `local/php_interface/init.php`:

```php
<?php

$autoload = $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

if (is_file($autoload)) {
    require_once $autoload;
}
```

Альтернатива — подключить `vendor/autoload.php` прямо в конкретном admin-файле, если глобальный bootstrap не нужен.

## Discovery

`AdminKitScope` состоит из:

- `scopeId` — идентификатор области AdminKit;
- `discoveryPaths` — директории, где AdminKit ищет `Resource` и `Page` классы.

Для Bitrix-модуля:

```php
use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\Manager\AdminKitScope;

Loader::includeModule('vendor.demo');
$scope = AdminKitScope::fromModuleId('vendor.demo');
```

Для нестандартной папки внутри модуля:

```php
$scope = AdminKitScope::fromModuleId('vendor.demo', 'lib/Resources');
```

Для нескольких папок внутри модуля:

```php
$scope = AdminKitScope::fromModuleId('vendor.demo', [
    'lib/Admin',
    'lib/Pages',
]);
```

Для локального кода вне модуля используйте абсолютный путь:

```php
$scope = AdminKitScope::fromDirectory(
    $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
    'local.admin'
);
```

Важно:

- `fromModuleId()` ищет модуль в `/local/modules/<moduleId>`, затем в `/bitrix/modules/<moduleId>`.
- `fromModuleId()` не подключает модуль и не вызывает `Loader::includeModule()`.
- `Loader::includeModule()` нужно вызвать отдельно, если вы работаете внутри Bitrix-модуля.

## Проверки в разработке

```bash
composer validate --strict
composer test
composer analyse
composer cs-check
```
