# Связи

## Когда использовать

Когда на форме/в гриде нужны связанные сущности и синхронизация отношений.

## Минимальный пример

```php
use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;

BelongsTo::make('Категория', 'CATEGORY_ID', CategoryTable::class);

BelongsToMany::make('Теги', 'TAGS')
    ->relation('TAGS')
    ->relatedTable(TagTable::class)
    ->pivotTable(ProductTagTable::class)
    ->foreignPivotKey('PRODUCT_ID')
    ->relatedPivotKey('TAG_ID');
```

Поддерживаемые relation-поля находятся в `MB\Bitrix\AdminKit\Field\Relation\*`.

## Ограничения

- Старые namespace вида `MB\Bitrix\AdminKit\Field\BelongsTo` не используйте.
- Для `BelongsToMany` full ORM-mode требует корректной конфигурации relation/pivot.

## См. также

- [Reference: Fields](../reference/fields/README.md)
- [Cookbook: entity selector](../cookbook/entity-selector.md)
