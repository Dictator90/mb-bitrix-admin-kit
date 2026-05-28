# BelongsToMany

Класс: `MB\Bitrix\AdminKit\Field\Relation\BelongsToMany`.

Назначение: множественная связь.

## Доступные методы

- `asCheckboxes(bool $v = true)` — рендерит форму как список checkbox вместо multi-select.
- `storedAsCsv(bool $enabled = true)` — включает/выключает хранение ID в CSV-колонке.
- `saveUsingOrm()` — сохраняет связь через ORM relation mode.
- `saveUsingManualSync()` — принудительно включает ручную синхронизацию pivot-таблицы.

Особенности:
- поддерживает CSV-хранение;
- поддерживает ORM/pivot-режим для object-graph persistence.

Пример:
```php
BelongsToMany::make('Теги', 'TAGS')
    ->relation('TAGS')
    ->relatedTable(TagTable::class)
    ->pivotTable(ProductTagTable::class)
    ->foreignPivotKey('PRODUCT_ID')
    ->relatedPivotKey('TAG_ID');
```

## Значения по умолчанию

- `storedAsCsv = true`
- `saveStrategy = "orm"`
- `ormSaveExplicit = false`
- `asCheckboxes = false`

Остальные дефолты наследуются из `BelongsTo`.
