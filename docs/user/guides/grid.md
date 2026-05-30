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

## Дополнительно

- **Кнопки тулбара** — `toolbarActions()` + `ToolbarAction` (ссылки, дропдаун-меню, split, side-panel, позиция). См. [Actions reference → ToolbarAction](../reference/actions.md).
- **Стандартная кнопка «Создать»** — `showCreateButton()` (скрыть) и `createButtonLabel()` (своя подпись).
- **Быстрый поиск** — `searchColumns()` задаёт колонки строки поиска тулбара. См. [Filters reference](../reference/filters.md).
- **Без пагинации** — `showPagination(): false` выводит все записи одной страницей. См. [Grid (концепт)](../../grid.md).
- **Экспорт** — выключен по умолчанию, включается `exportEnabled(): true`. См. [Import/Export](import-export.md).

## См. также

- [Grid (концепт)](../../grid.md)
- [Bulk actions guide](bulk-actions.md)
- [Actions reference](../reference/actions.md)
