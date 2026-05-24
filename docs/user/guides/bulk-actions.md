# Guide: безопасные Bulk actions

## Цель

Реализовать массовые операции без риска случайной обработки всей таблицы.

## Практический пример

```php
use MB\Bitrix\AdminKit\Action\BulkAction;

public function bulkActions(): iterable
{
    return [
        BulkAction::make('activate', 'Активировать')
            ->allowRunByFilter()
            ->confirm('Активировать выбранные записи?')
            ->handle(function (\MB\Bitrix\AdminKit\Database\BulkOperationContext $context) {
                // обработка selected IDs или run-by-filter
            }),

        BulkAction::delete(),
    ];
}
```

## selected IDs vs all-records-by-filter

- `selected IDs`: обрабатывайте только переданные ID.
- `all-records-by-filter`: разрешайте только через `allowRunByFilter()`.
- Пустой фильтр для all-records — только через `allowRunWithoutFilter()`.

## Лимиты и QueryGuard

- Ограничивайте объем через `maxBulkRows()`.
- Выполняйте обработку чанками (`bulkChunkSize()`).
- Не отключайте guard-механизмы для опасных действий.

## Частые ошибки

- Запуск destructive action “для всех” без явного opt-in.
- Игнорирование permission-проверок на запись.
- Ожидание полной автоматической отрисовки всех ошибок в UI.

## См. также

- [Bulk actions (overview)](../../bulk-actions.md)
- [Actions reference](../reference/actions.md)
- [Permissions](permissions.md)
