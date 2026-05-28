# IblockElementSelect

Класс: `MB\Bitrix\AdminKit\Field\IblockElementSelect`.

Назначение: выбор элемента инфоблока.

## Доступные методы

- `iblockId(int $iblockId)` — фиксирует конкретный инфоблок для выбора элементов.
- `dependsOn('IBLOCK_ID')` — реактивно обновляет список элементов при изменении поля инфоблока.

Пример:
```php
IblockElementSelect::make('Элемент', 'ELEMENT_ID')
    ->iblockId(5);
```

## Значения по умолчанию

- `iblockId = 0`.
- В конструкторе вызывается `iblockId($iblockId)`, поэтому по умолчанию настраивается entity `iblock-element-list` с `iblockId = 0`.
