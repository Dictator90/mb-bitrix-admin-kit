# Быстрый старт

AdminKit можно использовать двумя способами:

> Важно: для ORM CRUD используйте `DataManagerResource`. `CrudResource` хранит DSL страниц/полей/действий, но сам по себе не включает persistence и возвращает `hasCrud(): false`.


- внутри собственного Bitrix-модуля;
- вне модуля, например через `local/admin`, `local/classes` и Composer autoload проекта.

`Loader::includeModule()` и `AdminKit` решают разные задачи. `Loader::includeModule('vendor.demo')` подключает модуль, его `include.php` и классы. Менеджер `AdminKit` хранит `scopeId` и пути discovery, по которым AdminKit ищет `Resource` и `Page` классы.

## Вариант 1. Внутри Bitrix-модуля

Пример структуры:

```text
local/modules/vendor.demo/
├── admin/
│   ├── demo_admin.php
│   └── menu.php
├── include.php
├── lib/
│   ├── Admin/ProductResource.php
│   └── Orm/ProductTable.php
└── composer.json
```

### 1. Установка

```bash
cd local/modules/vendor.demo
composer require mb4it/bitrix-admin-kit
```

### 2. `include.php`

```php
<?php

declare(strict_types=1);

$vendorAutoload = __DIR__ . '/vendor/autoload.php';

if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}
```

`Loader::includeModule('vendor.demo')` подключит `include.php` модуля. Если зависимости установлены внутри модуля, используется `local/modules/vendor.demo/vendor`; если зависимости установлены в корне проекта, сработает fallback на проектный `vendor`.

### 3. ORM table

`lib/Orm/ProductTable.php`:

```php
<?php

declare(strict_types=1);

namespace Vendor\Demo\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

final class ProductTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'vendor_demo_product';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
            (new StringField('NAME'))->configureRequired(),
            new StringField('TYPE'),
            new StringField('ACTIVE'),
        ];
    }
}
```

### 4. `ProductResource`

`lib/Admin/ProductResource.php`:

```php
<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Filter\Types\SelectFilter;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use Vendor\Demo\Orm\ProductTable;

final class ProductResource extends DataManagerResource
{
    protected string $title = 'Товары';

    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function indexFields(): iterable
    {
        return [
            ID::make('ID'),
            Text::make('Название', 'NAME'),
            Select::make('Тип', 'TYPE')->options(['simple' => 'Simple', 'service' => 'Service']),
            Switcher::make('Активность', 'ACTIVE')->values('Y', 'N'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            Text::make('Название', 'NAME')->required(),
            Select::make('Тип', 'TYPE')->options(['simple' => 'Simple', 'service' => 'Service'])->default('simple'),
            Switcher::make('Активность', 'ACTIVE')->values('Y', 'N')->default('Y'),
        ];
    }

    public function filters(): iterable
    {
        return [
            TextFilter::make('Название', 'NAME'),
            SelectFilter::make('Тип', 'TYPE')->options(['simple' => 'Simple', 'service' => 'Service'])->exact(),
        ];
    }

    public function rowActions(): iterable
    {
        return [
            RowAction::edit(),
            RowAction::delete(),
        ];
    }

    public function bulkActions(): iterable
    {
        return [
            BulkAction::delete(),
        ];
    }
}
```

### 5. `admin/demo_admin.php`

```php
<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\AdminKit;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

global $APPLICATION, $adminPage;
$adminPage->hideTitle();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

Loader::includeModule('vendor.demo');

AdminKit::forModule('vendor.demo')->getCurrentPage()->render();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

Важно:

- `Loader::includeModule('vendor.demo')` подключает модуль и его `include.php`.
- `AdminKit::forModule('vendor.demo')` автоматически находит путь модуля и добавляет discovery path `lib/Admin` по умолчанию.
- Если ресурсы лежат в другой директории (например, `lib/Resources`), настройте пути сканирования через метод `discoverIn()`:
  ```php
  AdminKit::forModule('vendor.demo')
      ->discoverIn($_SERVER['DOCUMENT_ROOT'] . '/local/modules/vendor.demo/lib/Resources')
      ->getCurrentPage()
      ->render();
  ```

### 6. `admin/menu.php`

```php
<?php

use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\AdminKit;

Loader::includeModule('vendor.demo');

return [
    'parent_menu' => 'global_menu_content',
    'section' => 'vendor_demo',
    'sort' => 100,
    'text' => 'Демо модуль',
    'title' => 'Демо модуль',
    'icon' => 'adm-menu-settings',
    'items_id' => 'vendor_demo_menu',
    'items' => AdminKit::forModule('vendor.demo')->getMenu('/bitrix/admin/demo_admin.php'),
];
```

### 7. Проверка

1. Создайте таблицу `vendor_demo_product`.
2. Откройте `/bitrix/admin/demo_admin.php?page=product`.
3. Проверьте список, создание, редактирование, удаление и массовое удаление.

`page=product` берётся из `ProductResource::getId()`. Если у ресурса другой id, URL тоже будет другим.

## Вариант 2. Вне модуля / local admin page

Пример структуры:

```text
local/
├── admin/products_admin.php
├── classes/Admin/ProductResource.php
├── classes/Orm/ProductTable.php
└── php_interface/init.php

vendor/
└── autoload.php
```

### 1. Установка в корне проекта

```bash
cd <bitrix-project-root>/local/php_interface #Как базовый путь (но Вы вправе делать что вам хочется)
composer init #Если не было
composer require mb4it/bitrix-admin-kit
```

### 2. `local/php_interface/init.php`

```php
<?php

$autoload = __DIR__ . '/vendor/autoload.php';

if (is_file($autoload)) {
    require_once $autoload;
}
```

Если вы не хотите подключать Composer autoload глобально, подключите `vendor/autoload.php` в конкретном admin-файле до вызова фасада `AdminKit`.

### 3. Resource вне модуля

Код ресурса совпадает с модульным примером выше, но меняются namespace и imports:

```php
<?php

declare(strict_types=1);

namespace Local\Admin;

use Local\Orm\ProductTable;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;

final class ProductResource extends DataManagerResource
{
    // Остальная реализация такая же, как в примере Vendor\Demo\Admin\ProductResource.
}
```

ORM table в этом сценарии можно положить в `local/classes/Orm/ProductTable.php` с namespace `Local\Orm`.

### 5. `local/admin/products_admin.php`

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

### 6. Проверка

Откройте:

```text
/bitrix/admin/products_admin.php?page=product
```

`page=product` берётся из `ProductResource::getId()`. Если resource id другой, URL будет другим.
