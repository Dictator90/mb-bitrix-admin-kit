# Как добавить bulk action

```php
use MB\Bitrix\AdminKit\Action\BulkAction;

public function bulkActions(): iterable
{
    return [
        BulkAction::delete(),
    ];
}
```

Запуск по фильтру включайте только осознанно: `allowRunByFilter()`.

См. также: [Guide: Bulk actions](../guides/bulk-actions.md)
