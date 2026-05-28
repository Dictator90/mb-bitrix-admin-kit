# IblockSelect

Класс: `MB\Bitrix\AdminKit\Field\IblockSelect`.

Назначение: выбор инфоблока.

## Доступные методы

Специфичных методов у `IblockSelect` нет: класс преднастроен на сущность `iblock-list`.

Общий API `Field`: [field.md](field.md).

Пример:
```php
IblockSelect::make('Инфоблок', 'IBLOCK_ID');
```

## Значения по умолчанию

- В конструкторе сразу задаётся `entity("iblock-list")`.
- В конструкторе сразу задаётся стандартный `resolveLabels()` для активных инфоблоков.
