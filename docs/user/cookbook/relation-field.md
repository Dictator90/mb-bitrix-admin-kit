# Relation Field

## Задача

Показать и редактировать связанные данные в CRUD-форме и grid.

## Когда использовать

Когда сущности связаны отношениями `BelongsTo/HasOne/HasMany/BelongsToMany`.

## Решение

Используйте relation-поля из `Field\Relation\*` в `indexFields()/formFields()`. Для чтения связанных меток применяйте `displayUsing()` или preload/resolver, а не ручные JOIN в grid-слое.

## Полный пример

```php
use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use MB\Bitrix\AdminKit\Field\Relation\HasMany;

public function formFields(): iterable
{
    return [
        BelongsTo::make('Category', 'CATEGORY_ID'),
        HasMany::make('Tags', 'TAGS'),
    ];
}
```

## Как это работает

Relation-поля интегрируются в общий field pipeline и batch-loading связей после загрузки базовых строк index.

## Что важно учесть

- Не переносите relation JOIN-логику в `Grid`/`IndexPage` вручную.
- Для `BelongsToMany` и sync проверяйте поддержку на уровне конкретного ресурса/ORM.
- При составных PK учитывайте ограничения вашего ORM-мэппинга.

## Связанные разделы

- [Guides: Relations](../guides/relations.md)
- [Reference: Fields](../reference/fields/README.md)
- [Resources](../../resources.md)
