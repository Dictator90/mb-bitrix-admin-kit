# UfField

Класс: `MB\Bitrix\AdminKit\Field\UfField`.

Назначение: адаптер Bitrix User Fields.

## Доступные методы

- `entityId(string $entityId)` — задает UF-entity ID (например, `IBLOCK_5_SECTION`), откуда читать метаданные.
- `metadata(array $metadata)` — вручную задает метаданные UF-поля (тип, множественность, mandatory и т.д.).

Особенности:
- учитывает `MULTIPLE` и `MANDATORY` из метаданных UF.

Пример:
```php
UfField::make('UF_TEXT', 'UF_TEXT')
    ->entityId('IBLOCK_5_SECTION');
```

## Значения по умолчанию

- `entityId = ""` (не задан).
- `metadata = null` (ленивая загрузка метаданных).
- `multiple` и `required` автоматически синхронизируются из UF-метаданных после `metadata()` или `getMetadata()`.
