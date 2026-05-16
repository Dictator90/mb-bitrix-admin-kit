# Quick start: первый CRUD в Bitrix-модуле

Путь ниже показывает минимальный рабочий сценарий для модуля `vendor.demo`.

## 1. Установка

```bash
cd local/modules/vendor.demo
composer require mb4it/bitrix-admin-kit
```

В `include.php` подключите autoload:

```php
<?php

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
```

## 2. ORM DataManager

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

Создайте таблицу штатной установкой модуля или миграцией проекта.

## 3. ProductResource

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
use MB\Bitrix\AdminKit\Resource\CrudResource;
use Vendor\Demo\Orm\ProductTable;

final class ProductResource extends DataManagerResource
{
    protected string $title = 'Products';

    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function indexFields(): iterable
    {
        return [
            ID::make('ID'),
            Text::make('Name', 'NAME'),
            Select::make('Type', 'TYPE')->options(['simple' => 'Simple', 'service' => 'Service']),
            Switcher::make('Active', 'ACTIVE')->values('Y', 'N'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            Text::make('Name', 'NAME')->required(),
            Select::make('Type', 'TYPE')->options(['simple' => 'Simple', 'service' => 'Service'])->default('simple'),
            Switcher::make('Active', 'ACTIVE')->values('Y', 'N')->default('Y'),
        ];
    }

    public function filters(): iterable
    {
        return [
            TextFilter::make('Name', 'NAME'),
            SelectFilter::make('Type', 'TYPE')->options(['simple' => 'Simple', 'service' => 'Service'])->exact(),
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

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/vendor.demo/include.php';

use Vendor\Demo\Admin\ProductResource;

$resource = new ProductResource();
$action = (string)($_REQUEST['action'] ?? 'index');
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : null;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

match ($action) {
    'add' => $resource->formPage()->render(),
    'edit' => $resource->formPage($id)->render(),
    default => $resource->indexPage()->render(),
};

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

## 5. menu.php

`admin/menu.php`:

```php
<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/vendor.demo/include.php';

use Vendor\Demo\Admin\ProductResource;

return [[
    'parent_menu' => 'global_menu_content',
    'section' => ProductResource::getId(),
    'sort' => ProductResource::getSort(),
    'text' => 'Products',
    'title' => 'Products',
    'url' => 'demo_admin.php?page=' . ProductResource::getId(),
    'icon' => ProductResource::getMenuIcon(),
]];
```

## 6. Открытие страницы

1. Установите модуль и создайте таблицу `vendor_demo_product`.
2. Откройте админку Bitrix.
3. Перейдите в раздел меню `Products` или откройте `/bitrix/admin/demo_admin.php?page=product`.

## 7. Создание записи

На странице списка нажмите кнопку добавления. Заполните `Name`, `Type`, `Active` и сохраните. Форма вызовет `ProductTable::add()` через `CrudPersister`.

## 8. Редактирование записи

В меню строки выберите `Редактировать`. Форма загрузит запись по `ID`, нормализует POST через Field и вызовет `ProductTable::update()`.

## 9. Удаление записи

В меню строки выберите `Удалить` или отметьте несколько строк и выполните bulk delete. Удаление проверяет CSRF, выбранные ID и права Resource.
