# Подключение внутри Bitrix-модуля

## Что соберем

Минимальный модуль `vendor.demo` с:
- `DataManagerResource` (CRUD список/форма);
- `OptionsPage`;
- пунктом меню;
- admin-файлом, который рендерит страницы через `AdminKit`.

## Пример структуры

```text
local/modules/vendor.demo/
  composer.json
  include.php
  admin/
    menu.php
    vendor_demo_admin.php
  lib/
    Admin/
      ProductResource.php
      SettingsPage.php
    Entity/
      ProductTable.php
```

## 1) Composer

```json
{
  "require": {
    "mb4it/bitrix-admin-kit": "^1.0"
  },
  "autoload": {
    "psr-4": {
      "Vendor\\Demo\\": "lib/"
    }
  }
}
```

## 2) Где подключать `vendor/autoload.php`

### Вариант A: vendor в корне проекта
Если проект уже подключает корневой `vendor/autoload.php`, в модуле отдельный require обычно не нужен.

### Вариант B: vendor внутри модуля
`local/modules/vendor.demo/include.php`:

```php
<?php

declare(strict_types=1);

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
```

## 3) Resource

`local/modules/vendor.demo/lib/Admin/ProductResource.php`:

```php
<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use Vendor\Demo\Entity\ProductTable;

final class ProductResource extends DataManagerResource
{
    protected string $title = 'Products';

    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function indexFields(): iterable
    {
        return [ID::make('ID', 'ID'), Text::make('Name', 'NAME')];
    }

    public function formFields(): iterable
    {
        return [Text::make('Name', 'NAME')->required()];
    }

    public function filters(): iterable
    {
        return [TextFilter::make('Name', 'NAME')->contains()];
    }

    public function rowActions(): iterable
    {
        return [RowAction::view(), RowAction::edit(), RowAction::delete()];
    }

    public function bulkActions(): iterable
    {
        return [BulkAction::delete()];
    }
}
```

## 4) OptionsPage

`local/modules/vendor.demo/lib/Admin/SettingsPage.php`:

```php
<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Field\Password;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Page\Standalone\OptionsPage;

final class SettingsPage extends OptionsPage
{
    public static function getId(): string
    {
        return 'vendor_demo_settings';
    }

    public static function getTitle(): string
    {
        return 'Module settings';
    }

    protected string $moduleId = 'vendor.demo';

    public function fields(): iterable
    {
        return [
            Text::make('API URL', 'api_url'),
            Password::make('API key', 'api_key'),
            Switcher::make('Enabled', 'enabled')->default(true),
        ];
    }
}
```

## 5) Меню модуля

`local/modules/vendor.demo/admin/menu.php`:

```php
<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\AdminKit;

Loader::includeModule('vendor.demo');

return [
    'parent_menu' => 'global_menu_services',
    'section' => 'vendor_demo',
    'sort' => 100,
    'text' => 'Demo admin',
    'title' => 'Demo admin',
    'items_id' => 'menu_vendor_demo',
    'items' => AdminKit::forModule('vendor.demo')->getMenu('/bitrix/admin/vendor_demo_admin.php'),
];
```

## 6) Admin-файл

`local/modules/vendor.demo/admin/vendor_demo_admin.php`:

```php
<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\AdminKit;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
Loader::includeModule('vendor.demo');
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

AdminKit::forModule('vendor.demo')->getCurrentPage()->render();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

## 7) Как открыть

- Через меню раздела `Demo admin`.
- Или напрямую: `/bitrix/admin/vendor_demo_admin.php?lang=ru`.

## Частые ошибки

- Не подключен `vendor/autoload.php`.
- Нет `Loader::includeModule('vendor.demo')`.
- В `DataManagerResource` указан `public static function dataManagerClass()` вместо `public function`.
- URL меню не совпадает с реальным admin-файлом.
