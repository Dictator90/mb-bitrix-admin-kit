# First CRUD Resource

## Задача

Создать первый CRUD-раздел для D7 ORM-сущности.

## Когда использовать

Когда у вас уже есть `DataManager` и нужно быстро получить index/form/detail с фильтрами и действиями.

## Решение

Наследуйте ресурс от `DataManagerResource`: он уже реализует базовый CRUD-поток, работу с Grid и form-пайплайн. Опишите ORM-класс через `dataManagerClass()`, затем добавьте поля для index/form и фильтры.

Действия строк и bulk-действия добавляйте сразу в ресурсе: так права, URL и поведение action panel остаются в одном месте.

## Полный пример

```php
<?php

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;

final class ProductResource extends DataManagerResource
{
    public static function label(): string
    {
        return 'Products';
    }

    public static function dataManagerClass(): string
    {
        return \Vendor\Module\Internals\ProductTable::class;
    }

    public function indexFields(): iterable
    {
        return [
            ID::make('ID')->sortable(),
            Text::make('Name', 'NAME')->sortable()->asEditLink(),
        ];
    }

    public function formFields(): iterable
    {
        return [
            Text::make('Name', 'NAME')->required(),
        ];
    }

    public function filters(): iterable
    {
        return [
            TextFilter::make('Name', 'NAME')->contains(),
        ];
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

## Как это работает

`DataManagerResource` берет ORM-данные через `dataManagerClass()` и передает конфигурацию полей/фильтров в `GridQueryBuilder` и UI-адаптеры. `indexFields()` управляет колонками списка, `formFields()` — формой create/edit.

## Что важно учесть

- Для Bitrix Grid используется `bitrix:main.ui.grid`; фильтры идут через `main.ui.filter`.
- `BulkAction` безопасен по умолчанию: без selected ID операция не выполняется, если явно не разрешен режим по фильтру.
- Права (`canView/canCreate/canUpdate/canDelete`) настраивайте в ресурсе до включения destructive actions.

## Связанные разделы

- [Quick start](../../quick-start.md)
- [Resources](../../resources.md)
- [Fields](../../fields.md)
- [Grid](../../grid.md)
- [Actions](../../actions.md)
- [Reference: Resources](../reference/resources.md)
