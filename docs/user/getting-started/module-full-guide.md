# Полный гайд: подключение в Bitrix-модуле

## Когда использовать

Когда AdminKit подключается внутри собственного Bitrix-модуля (`local/modules/<module_id>`).

## Шаг 1. Установите пакет

Откройте директорию модуля и установите пакет:

```bash
cd local/modules/vendor.demo
composer require mb4it/bitrix-admin-kit
```

## Шаг 2. Подключите autoload в `include.php`

Файл: `local/modules/vendor.demo/include.php`

```php
<?php

declare(strict_types=1);

$moduleVendor = __DIR__ . '/vendor/autoload.php';

if (is_file($moduleVendor)) {
    require_once $moduleVendor;
}
```

Это покрывает оба сценария:
- зависимости стоят в модуле (`local/modules/vendor.demo/vendor`);
- зависимости стоят в корне проекта (`<docroot>/vendor`).

## Шаг 3. Создайте admin-обработчик

Файл: `/bitrix/admin/vendor_demo_admin.php`

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

## Шаг 4. (Опционально) подключите меню модуля

Файл: `local/modules/vendor.demo/admin/menu.php`

```php
<?php

use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\AdminKit;

Loader::includeModule('vendor.demo');

return [
    'parent_menu' => 'global_menu_content',
    'section' => 'vendor_demo',
    'sort' => 150,
    'text' => 'Демо-модуль',
    'title' => 'Панель демо-модуля',
    'icon' => 'adm-menu-settings',
    'items_id' => 'vendor_demo_menu',
    'items' => AdminKit::forModule('vendor.demo')->getMenu('/bitrix/admin/vendor_demo_admin.php'),
];
```

## Шаг 5. Проверьте открытие страницы

Откройте admin-файл модуля, например:

```text
/bitrix/admin/vendor_demo_admin.php
```

Если ресурсы обнаружены, `AdminKit` откроет текущую страницу по роутингу.

## Ограничения

- `AdminKit::forModule()` не вызывает `Loader::includeModule()` автоматически.
- Import UI на index-страницах временно отключен.

## См. также

- [First CRUD](first-crud-resource.md)
- [Discovery и routing](../guides/discovery-routing.md)
