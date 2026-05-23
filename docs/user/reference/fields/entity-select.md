# EntitySelect

Класс: `MB\Bitrix\AdminKit\Field\EntitySelect`.

Назначение: универсальный selector на `ui.entity-selector`.

## Доступные методы

- `entityId(string $entityId, array $options = [])` — задает основную сущность selector-а и ее опции.
- `entity(string $id, array $entityOptions = [])` — добавляет дополнительную сущность в диалог.
- `resetEntities()` — очищает ранее добавленные сущности.
- `resolveLabels(Closure $resolver)` — задает callback для получения читаемых названий по ID.

Особенность:
- multi-значение сериализуется в CSV-строку.

Общий API `Field` (в т.ч. `multiple()`): [field.md](field.md).

Пример:
```php
EntitySelect::make('Склад', 'WAREHOUSE_ID')
    ->entityId('warehouse');
```

## Значения по умолчанию

- `entityId = "user"`
- `entityOptions = []`
- `entities = []`
- `labelResolver = null`
- `multiple = false` (унаследовано, включается через `multiple()`).
