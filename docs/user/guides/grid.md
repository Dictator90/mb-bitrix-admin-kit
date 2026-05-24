# Guide: настройка Grid

## Задача

Настроить index-список: колонки, сортировку, фильтры, row actions и bulk actions безопасным способом.

## Полный пример Resource

```php
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Filter\TextFilter;

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
        TextFilter::make('Название', 'NAME'),
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
```

## Пояснение по частям

1. `indexFields()` управляет колонками и sort/edit-link behavior.
2. `filters()` связывает UI filter и ORM filter pipeline.
3. `rowActions()` задает действия на уровне одной записи.
4. `bulkActions()` задает безопасные массовые операции через action panel.

## Частые ошибки

- Использование несуществующих field/action методов.
- Ожидание, что `SHOW_SELECT_ALL_RECORDS_CHECKBOX` сам по себе безопасно обработает все записи.
- Тяжелые выборки без лимитов и без QueryGuard.
- Дублирование grid-конфигурации одновременно в `Page` и `Resource` без необходимости.

## См. также

- [Grid (концепт)](../../grid.md)
- [Bulk actions guide](bulk-actions.md)
- [Actions reference](../reference/actions.md)
