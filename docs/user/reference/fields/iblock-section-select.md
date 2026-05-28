# IblockSectionSelect

Класс: `MB\Bitrix\AdminKit\Field\IblockSectionSelect`.

Назначение: выбор раздела инфоблока.

## Доступные методы

- `iblockId(int $iblockId)` — ограничивает выбор разделов указанным инфоблоком.

Пример:
```php
IblockSectionSelect::make('Раздел', 'SECTION_ID')
    ->iblockId(5);
```

## Значения по умолчанию

- `iblockId = 0`.
- В конструкторе вызывается `iblockId($iblockId)`, поэтому по умолчанию настраивается entity `iblock-section-list` с `iblockId = 0`.
