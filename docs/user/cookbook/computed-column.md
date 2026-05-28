# Как сделать computed column

```php
use MB\Bitrix\AdminKit\Field\Text;

Text::make('Статус', 'STATUS_LABEL')
    ->computed(static fn (array $row): string => ($row['ACTIVE'] ?? 'N') === 'Y' ? 'Активен' : 'Неактивен');
```

См. также: [Guide: Grid](../guides/grid.md)
