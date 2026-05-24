# Add Grid column

## Задача

Добавить новую колонку в index Grid ресурса.

## Когда использовать

Когда нужно вывести дополнительное поле, сортировку или вычисляемое значение в списке.

## Решение

Добавляйте колонку в `indexFields()`. Для сортируемых колонок включайте `sortable()`. Для колонки-навигации на редактирование используйте `asEditLink()`.

`formFields()` и `detailFields()` не обязаны совпадать с `indexFields()`: список оптимизируют под быстрый обзор, форму — под редактирование.

## Полный пример

```php
public function indexFields(): iterable
{
    return [
        ID::make('ID')->sortable(),
        Text::make('Name', 'NAME')->sortable()->asEditLink(),
        Text::make('Status', 'ACTIVE')
            ->displayUsing(static fn (string $value): string => $value === 'Y' ? 'Active' : 'Disabled'),
    ];
}
```

## Как это работает

`displayUsing()` оставляет исходное ORM-поле неизменным, но меняет вывод в UI. Это удобно для computed column без runtime JOIN/ALTER.

## Что важно учесть

- Для сортировки используйте реальные ORM-колонки.
- Не переносите тяжелую бизнес-логику в рендер поля.
- Для связанных данных лучше preload через relation field/resolver, чтобы избежать N+1.

## Связанные разделы

- [Grid](../../grid.md)
- [Fields](../../fields.md)
- [Reference: Fields](../reference/fields/README.md)
