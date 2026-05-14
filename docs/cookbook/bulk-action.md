# Как добавить bulk action

```php
public function bulkActions(): iterable
{
    return [
        BulkAction::make('activate', 'Activate')->update(['ACTIVE' => 'Y']),
        BulkAction::delete(),
    ];
}
```

Bulk actions требуют явные selected IDs. Запуск по фильтру включайте только осознанно через `allowRunByFilter()`.
