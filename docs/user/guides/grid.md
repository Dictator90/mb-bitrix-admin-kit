# Guide: настройка Grid

## Задача

Настроить index-список: колонки, сортировку, фильтры, row actions и bulk actions безопасным способом.

## Полный пример Resource

```php
<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin\Resource;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use Vendor\Demo\Orm\ProductTable;

final class ProductResource extends DataManagerResource
{
    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function indexFields(): iterable
    {
        return [
            ID::make('ID', 'ID')->sortable(),
            Text::make('Название', 'NAME')->sortable()->asEditLink(),
            Text::make('Код', 'CODE')->sortable(),
        ];
    }

    public function filters(): iterable
    {
        return [
            TextFilter::make('Название', 'NAME')->contains(),
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
            BulkActionDropdown::make('state', 'Статус')->items([
                BulkAction::make('activate', 'Активировать')->allowRunByFilter(),
                BulkAction::make('deactivate', 'Деактивировать')->allowRunByFilter(),
            ]),
            BulkAction::delete(),
        ];
    }
}
```

## См. также

- [Grid (концепт)](../../grid.md)
- [Bulk actions guide](bulk-actions.md)
- [Actions reference](../reference/actions.md)
