# Как добавить BulkAction

## Задача

Добавить массовое действие для выбранных строк и безопасно контролировать запуск «по фильтру».

## Решение

Опишите действия в `bulkActions()` и включайте `allowRunByFilter()` только для осознанных сценариев.

## Полный пример

```php
use MB\Bitrix\AdminKit\Action\BulkAction;

public function bulkActions(): iterable
{
    return [
        BulkAction::delete(),

        BulkAction::make('activate', 'Активировать')
            ->confirm('Активировать выбранные записи?')
            ->allowRunByFilter()
            ->handle(function (\MB\Bitrix\AdminKit\Database\BulkOperationContext $context) {
                // обработка selectedIds или run-by-filter
            }),
    ];
}
```

## Что важно учесть

- `BulkActionDropdown` — только UI-контейнер, не исполнитель.
- Проверяйте CSRF, `canRun/canSee` и per-item permissions (`canUpdate`/`canDelete`).
- Для all-records режима применяйте QueryGuard и лимиты.

## Связанные разделы

- [Bulk actions](../../bulk-actions.md)
- [Guide: Bulk actions](../guides/bulk-actions.md)
- [Guide: Performance & diagnostics](../guides/performance-diagnostics.md)
