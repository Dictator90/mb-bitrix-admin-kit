# HasOne

Класс: `MB\Bitrix\AdminKit\Field\Relation\HasOne`.

Назначение: связь один-к-одному.

## Доступные методы

- `asPreview()` — отображает связанную сущность в режиме превью (read-only).
- `asEmbeddedForm()` — включает встроенный preview-режим для вложенного блока.

Пример:
```php
HasOne::make('Профиль', 'PROFILE')->asPreview();
```

## Значения по умолчанию

- `renderMode = "preview"`.
- Для relation-настроек используются дефолты `RelationField` (включая `readonly = true`).
