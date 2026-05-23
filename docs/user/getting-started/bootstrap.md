# Bootstrap (модуль и standalone)

> Краткая версия. Полные инструкции от установки пакета:
> - [Полный гайд для модуля](module-full-guide.md)
> - [Полный гайд для standalone](standalone-full-guide.md)

## Когда использовать

Когда нужно подключить AdminKit к админ-странице.

## Полный процесс: внутри Bitrix-модуля

1. Убедитесь, что пакет установлен (`composer require mb4it/bitrix-admin-kit`) и autoload подключается в `include.php` модуля.
2. Создайте admin-обработчик (например, `/bitrix/admin/vendor_demo_admin.php`).
3. В обработчике подключите модуль через `Loader::includeModule()`, скройте заголовок и вызовите `AdminKit::forModule(...)->getCurrentPage()->render()`.

### Пример admin-обработчика модуля

```php
<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\AdminKit;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
Loader::includeModule('vendor.demo');

global $APPLICATION, $adminPage;
$adminPage->hideTitle();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

AdminKit::forModule('vendor.demo')->getCurrentPage()->render();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

## Полный процесс: standalone (`local/admin`)

1. Убедитесь, что пакет установлен в контекст `local` или проекта.
2. Подключите Composer autoload в `local/php_interface/init.php`:

```php
<?php

$localVendor = $_SERVER['DOCUMENT_ROOT'] . '/local/vendor/autoload.php';
$projectVendor = $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

if (is_file($localVendor)) {
    require_once $localVendor;
} elseif (is_file($projectVendor)) {
    require_once $projectVendor;
}
```

3. Создайте admin-обработчик (например, `/local/admin/products_admin.php`) и рендерите AdminKit через `fromDirectory()`.

### Пример standalone-обработчика

```php
<?php

declare(strict_types=1);

use MB\Bitrix\AdminKit\AdminKit;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

global $APPLICATION, $adminPage;
$adminPage->hideTitle();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

AdminKit::fromDirectory(
    $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
    'local.admin'
)->getCurrentPage()->render();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

Windows-пример:
- проект: `F:\Projects\mb\bitrix-test`
- autoload в `F:\Projects\mb\bitrix-test\local\php_interface\init.php`
- admin-файл: `F:\Projects\mb\bitrix-test\local\admin\products_admin.php`

## Ограничения

- `AdminKit::forModule()` не вызывает `Loader::includeModule()` автоматически.
- Discovery-path должен вести к классам `Resource`/`StandalonePage`.

## См. также

- [Discovery и routing](../guides/discovery-routing.md)
- [Reference: UrlGenerator](../reference/url-generator.md)
