# Quick Start

## Что соберем

CRUD-раздел «Товары» в админке Bitrix: список (Grid), форма создания/редактирования (Form), фильтр и действия.

## 1. Установка

Установите пакет и подключите bootstrap: [Installation](installation.md).

## 2. ORM DataManager

Используйте ваш существующий D7 `DataManager`. Для примера:

```php
Vendor\Demo\Orm\ProductTable::class
```

## 3. Resource

Создайте ORM-ресурс на базе `DataManagerResource`.

```php
<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin\Resource;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Field\DateTime;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Field\Textarea;
use MB\Bitrix\AdminKit\Filter\Types\CheckboxFilter;
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
            ID::make('ID', 'ID'),
            Text::make('Название', 'NAME'),
            Switcher::make('Активен', 'ACTIVE'),
            DateTime::make('Создан', 'DATE_CREATE'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            Text::make('Название', 'NAME')->required(),
            Textarea::make('Описание', 'DESCRIPTION'),
            Switcher::make('Активен', 'ACTIVE')->default(true),
        ];
    }

    public function filters(): iterable
    {
        return [
            TextFilter::make('Название', 'NAME')->contains(),
            CheckboxFilter::make('Активен', 'ACTIVE'),
        ];
    }

    public function rowActions(): iterable
    {
        return [
            RowAction::view(),
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

## 4. Поля

В примере используются стандартные поля из публичного API:
- `ID`
- `Text`
- `Textarea`
- `Switcher`
- `DateTime`

Подробности: [Fields](fields.md).

## 5. Фильтры

Фильтры ресурса связываются с `main.ui.filter`.
В примере использованы:
- `TextFilter`
- `CheckboxFilter`

Подробности: [Filters reference](docs/user/reference/filters.md).

## 6. Row actions

Минимальный набор действий строки:
- `RowAction::view()`
- `RowAction::edit()`
- `RowAction::delete()`

Подробности: [Actions](actions.md).

## 7. Bulk actions

Для безопасного старта достаточно `BulkAction::delete()`.

Продвинутые сценарии (run by filter, custom handler): [Bulk actions](bulk-actions.md).

## 8. Подключение ресурса в админке

Регистрация через `AdminKitManager`:

```php
<?php

declare(strict_types=1);

use MB\Bitrix\AdminKit\AdminKit;
use Vendor\Demo\Admin\Resource\ProductResource;

$adminKit = AdminKit::forModule('vendor.demo');

$adminKit
    ->register(ProductResource::class)
    ->getCurrentPage()
    ->render();
```

Если вы используете directory-based сценарий, применяйте `AdminKit::fromDirectory(...)` из [Installation](installation.md).

## 9. Что дальше

- [Resources](resources.md)
- [Pages](pages.md)
- [Fields](fields.md)
- [Grid](grid.md)
- [Filters](docs/user/reference/filters.md)
- [Actions](actions.md)
- [Bulk actions](bulk-actions.md)
- [OptionsPage](options-page.md)
