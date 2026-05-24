# Как добавить фильтр

## Задача

Добавить фильтр для `main.ui.filter`, чтобы управлять ORM-выборкой в Grid.

## Решение

Опишите фильтр в `filters()` ресурса.

## Полный пример

```php
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;

public function filters(): iterable
{
    return [
        TextFilter::make('Название', 'NAME')->contains(),
    ];
}
```

## Что важно учесть

- Фильтр описывает UI (`main.ui.filter`) и правила формирования ORM-фильтра.
- Используйте конкретные Filter-классы, не подменяйте их Field-классами.

## Связанные разделы

- [Filters](../../filters.md)
- [Reference: Filters](../reference/filters.md)
- [Guide: Grid](../guides/grid.md)
