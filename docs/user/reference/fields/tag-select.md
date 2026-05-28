# TagSelect

Класс: `MB\Bitrix\AdminKit\Field\TagSelect`.

Назначение: алиас `EntitySelect`.

## Доступные методы

Специфичных методов у `TagSelect` нет: используется тот же API, что у `EntitySelect`.

См. подробно: [entity-select.md](entity-select.md).

Пример:
```php
TagSelect::make('Участники', 'PARTICIPANT_IDS')
    ->entity('user')
    ->entity('department')
    ->multiple();
```

## Значения по умолчанию

- Использует дефолты `EntitySelect` без собственных переопределений.
- По умолчанию `multiple = false` (можно включить через `multiple()`).
