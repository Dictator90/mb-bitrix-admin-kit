# Полный гайд: подключение вне модуля (standalone)

## Когда использовать

Когда админ-страницы живут в `local/admin`, без отдельного Bitrix-модуля.

## Шаг 1. Установите пакет

Перейдите в Composer-контекст проекта/локального слоя и установите пакет:

```bash
cd local
composer require mb4it/bitrix-admin-kit
```

## Шаг 2. Подключите autoload в `local/php_interface/init.php`

Файл: `local/php_interface/init.php`

```php
<?php

declare(strict_types=1);

# Путь к вендору 
$localVendor = $_SERVER['DOCUMENT_ROOT'] . '/local/vendor/autoload.php';

if (is_file($localVendor)) {
    require_once $localVendor;
}
```

## Шаг 3. Подготовьте директорию классов AdminKit

Создайте директорию с ресурсами/страницами, например:

```text
local/classes/Admin
```

Туда помещайте классы `DataManagerResource` и `Page\Standalone\*`.

## Шаг 4. Создайте standalone admin-обработчик

Файл: `bitrix/admin/products_admin.php`

```php
<?php

declare(strict_types=1);

use MB\Bitrix\AdminKit\AdminKit;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

global $APPLICATION, $adminPage;
$adminPage->hideTitle();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

/** 
 * local.admin - scope для AdminKit (обязательно, но в рамках "вне модуля" может называться как угодно)
 * Будет записывать в b_option и b_option_site MODULE_ID = {ваш scope}
 **/
AdminKit::fromDirectory(
    $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
    'local.admin'
)->getCurrentPage()->render();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

## Шаг 5. (Опционально) добавьте пункт в меню через событие

Пример регистрации в `local/php_interface/init.php`:

```php
<?php

use Bitrix\Main\EventManager;
use MB\Bitrix\AdminKit\AdminKit;

EventManager::getInstance()->addEventHandler(
    'main',
    'OnBuildGlobalMenu',
    static function (&$aGlobalMenu, &$aModuleMenu): void {
        $aModuleMenu[] = [
            'parent_menu' => 'global_menu_settings',
            'section' => 'local_admin',
            'sort' => 2000,
            'text' => 'Локальная админка',
            'title' => 'Локальная админка',
            'icon' => 'adm-menu-settings',
            'items_id' => 'local_admin_menu',
            'items' => AdminKit::fromDirectory(
                $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
                'local.admin'
            )->getMenu('/local/admin/products_admin.php'),
        ];
    }
);
```
## Шаг 7. Создайте ваши страницы или ресурсы

- [Первая Страница](first-standalone-page.md)
- [Первый Crud-Ресурс (ORM таблица)](first-crud-resource.md)
- 
## Шаг 6. Проверьте открытие страницы

Откройте:

```text
/bitrix/admin/products_admin.php
```

## Windows-пример (ваш сценарий)

- проект: `F:\Projects\mb\bitrix-test`
- установка пакета: `F:\Projects\mb\bitrix-test\local`
- autoload: `F:\Projects\mb\bitrix-test\local\vendor\autoload.php`
- init-файл: `F:\Projects\mb\bitrix-test\local\php_interface\init.php`
- admin-файл: `F:\Projects\mb\bitrix-test\local\admin\products_admin.php`

## Ограничения

- Import UI на index-страницах временно отключен.
- Путь в `fromDirectory()` должен вести к существующей директории с классами.

## См. также

- [Поиск и роутинг ресурсов](../guides/discovery-routing.md)
