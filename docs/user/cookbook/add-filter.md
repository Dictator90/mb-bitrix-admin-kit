# Add filter

## Задача

Добавить фильтрацию записей на index-странице.

## Когда использовать

Когда нужно искать по тексту, отбирать по статусу и датам.

## Решение

Опишите фильтры в `filters()`. `TextFilter` подходит для contains/exact, `SelectFilter` — для точного выбора из списка, `DateFilter` — для диапазона.

Фильтры автоматически подключаются к `main.ui.filter`, а `GridQueryBuilder` применяет их к ORM query.

## Полный пример

```php
use MB\Bitrix\AdminKit\Filter\Types\DateFilter;
use MB\Bitrix\AdminKit\Filter\Types\SelectFilter;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;

public function filters(): iterable
{
    return [
        TextFilter::make('Name', 'NAME')->contains(),
        SelectFilter::make('Status', 'ACTIVE')->exact()->options([
            'Y' => 'Active',
            'N' => 'Disabled',
        ]),
        DateFilter::make('Created', 'DATE_CREATE')->range(),
    ];
}
```

## Как это работает

`TextFilter::contains()` генерирует `%FIELD`-условие, `SelectFilter::exact()` отключает multi-select, `DateFilter::range()` формирует `>=FIELD` / `<=FIELD`.

## Что важно учесть

- Названия колонок должны совпадать с ORM-полями.
- Для сложных условий добавляйте отдельный безопасный runtime в query-слое, а не в UI-рендере.

## Связанные разделы

- [Filters](../../filters.md)
- [Grid](../../grid.md)
- [Reference: Filters](../reference/filters.md)
