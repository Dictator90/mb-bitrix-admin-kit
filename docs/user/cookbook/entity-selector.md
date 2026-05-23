# Как настроить entity selector

```php
use MB\Bitrix\AdminKit\Field\EntitySelect;

EntitySelect::make('Склад', 'WAREHOUSE_ID')
    ->entityId('warehouse');
```

Подробно: [Reference: Fields](../reference/fields/README.md)
