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

if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}
```

В admin-файле модуль нужно подключать явно:

```php
use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\AdminKit;

Loader::includeModule('vendor.demo');

AdminKit::forModule('vendor.demo')->getCurrentPage()->render();
```

`Loader::includeModule('vendor.demo')` подключает модуль, его `include.php` и классы. Вызов `AdminKit::forModule('vendor.demo')` возвращает экземпляр `AdminKitManager` для этого модуля, автоматически определяя его директорию и настраивая пути сканирования ресурсов и страниц (по умолчанию `lib/Admin`).

## Bootstrap вне модуля

Для `local/admin`-страниц без отдельного Bitrix-модуля устанавливайте пакет в корне проекта:

```bash
cd <bitrix-project-root>
composer require mb4it/bitrix-admin-kit
```

Подключите Composer autoload globally в `local/php_interface/init.php`:

```php
<?php

$autoload = $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

if (is_file($autoload)) {
    require_once $autoload;
}
```

Альтернатива — подключить `vendor/autoload.php` прямо в конкретном admin-файле, если глобальный bootstrap не нужен.

## Discovery

Инициализация и настройка путей сканирования ресурсов и страниц выполняется через фасад `AdminKit`.

Для стандартного Bitrix-модуля (сканирует `lib/Admin` внутри модуля):

```php
use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\AdminKit;

Loader::includeModule('vendor.demo');
$adminKit = AdminKit::forModule('vendor.demo');
```

Для нестандартной папки внутри модуля можно доопределить пути сканирования:

```php
$adminKit = AdminKit::forModule('vendor.demo')
    ->discoverIn($_SERVER['DOCUMENT_ROOT'] . '/local/modules/vendor.demo/lib/Resources');
```

Или настроить с помощью `AdminKitScope` явно:

```php
use MB\Bitrix\AdminKit\AdminKit;
use MB\Bitrix\AdminKit\Manager\AdminKitScope;

$scope = AdminKitScope::fromModuleId('vendor.demo', 'lib/Resources');
$adminKit = AdminKit::manager($scope);
```

Для нескольких папок внутри модуля:

```php
$scope = AdminKitScope::fromModuleId('vendor.demo', [
    'lib/Admin',
    'lib/Pages',
]);
$adminKit = AdminKit::manager($scope);
```

Для локального кода вне модуля:

```php
use MB\Bitrix\AdminKit\AdminKit;

$adminKit = AdminKit::fromDirectory(
    $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
    'local.admin'
);
```

Важно:
- `forModule()` ищет модуль в `/local/modules/<moduleId>`, затем в `/bitrix/modules/<moduleId>`.
- Инициализация менеджера не подключает модуль и не вызывает `Loader::includeModule()`.
- `Loader::includeModule()` нужно вызвать отдельно, если вы работаете внутри Bitrix-модуля.

## Проверки в разработке

```bash
composer validate --strict
composer test
composer analyse
composer cs-check
```
