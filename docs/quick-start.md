# Быстрый старт: первый CRUD в модуле Битрикс

Ниже минимальный рабочий сценарий для модуля `vendor.demo`.

## 1. Установка

```bash
cd local/modules/vendor.demo
composer require mb4it/bitrix-admin-kit
```

В `include.php` модуля:

```php
<?php

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
```

## 2. ORM-таблица

`lib/Orm/ProductTable.php`:

```php
<?php

namespace Vendor\Demo\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\DataManager;

final class ProductTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'vendor_demo_product';
    }

    public static function getMap(): array
    {
        return [
            (new Entity\IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
            (new Entity\StringField('NAME'))->configureRequired(),
            new Entity\StringField('TYPE'),
            new Entity\StringField('ACTIVE'),
        ];
    }
}
```

## 3. Resource

`lib/Admin/ProductResource.php`:

```php
<?php

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
        return [RowAction::edit(), RowAction::delete()];
    }

    public function bulkActions(): iterable
    {
        return [BulkAction::delete()];
    }
}
```

## 4. Admin-файл

`admin/demo_admin.php`:

```php
<?php

use Vendor\Demo\Admin\ProductResource;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

global $APPLICATION, $adminPage;
$adminPage->hideTitle();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$scope = \MB\Bitrix\AdminKit\Manager\AdminKitScope('vendor.demo', [__DIR__ . '/../lib/Admin'])
(new MB\Bitrix\AdminKit\Manager\AdminKitManager($scope))->getCurrentPage()->render();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

## 5. Пункт меню

`admin/menu.php`:

```php
<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/vendor.demo/include.php';

use Vendor\Demo\Admin\ProductResource;

return [
    'parent_menu' => 'global_menu_content',
    'section' => ProductResource::getId(),
    'sort' => ProductResource::getSort(),
    'text' => 'Товары',
    'title' => 'Товары',
    'url' => 'demo_admin.php?page=' . ProductResource::getId(),
    'icon' => ProductResource::getMenuIcon(),
];
```

## 6. Проверка

1. Создайте таблицу `vendor_demo_product`.
2. Откройте админку Битрикс.
3. Перейдите в пункт `Товары` или откройте `/bitrix/admin/demo_admin.php?page=product`.

После этого доступны базовые сценарии: список, создание, редактирование, удаление и массовое удаление.
