# Производительность и диагностика

## Когда использовать

Когда нужны лимиты запросов, кэширование, контроль total count и проверка схемы БД.

## Минимальный пример

```php
use MB\Bitrix\AdminKit\Grid\GridContext;

public function useTotalCount(GridContext $context): bool
{
    return false;
}

public function maxPageSize(): int
{
    return 200;
}
```

Диагностика схемы:
- `TableSchema`
- `TableHealthCheck`
- `DatabaseSchemaInspector`
- `Page\System\DatabaseHealthPage`

## Ограничения

- Диагностика БД должна оставаться read-only.
- Не выполняйте создание/изменение таблиц из admin-страниц.

## См. также

- [Reference: Resources](../reference/resources.md)
- [Guides: Grid](grid.md)
