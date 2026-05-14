# Как сделать computed column

```php
Text::make('Status', 'STATUS_LABEL')
    ->computed(static fn(array $row): string => $row['ACTIVE'] === 'Y' ? 'Active' : 'Inactive');
```

Computed column считается после загрузки строки и не добавляется в ORM `select` автоматически.
