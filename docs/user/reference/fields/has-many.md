# HasMany

Класс: `MB\Bitrix\AdminKit\Field\Relation\HasMany`.

Назначение: связь один-ко-многим.

## Доступные методы

- `asTable(array|null $columns = null)` — рендерит read-only таблицу (tilegrid-preview) связанных элементов.
- `asEmbeddedForm()` — включает embedded preview-режим.
- `asGrid()` — включает grid-like preview-режим (ограниченный, без полного CRUD).

Пример:
```php
HasMany::make('Группы', 'GROUPS')
    ->asTable(['ID', 'NAME']);
```

## Значения по умолчанию

- `renderMode = "table"`.
- `tableColumns = null` (авто-режим колонок в preview).
- `relationDefault()` возвращает пустой массив `[]`.
- Для relation-настроек используются дефолты `RelationField` (включая `readonly = true`).
