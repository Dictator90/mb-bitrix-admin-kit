# Add BulkAction

## Задача

Запустить массовую операцию по выбранным строкам или (опционально) по фильтру.

## Когда использовать

Для массового удаления, смены статуса, пересчета атрибутов.

## Решение

Используйте `bulkActions()` и возвращайте `BulkAction::delete()` либо `BulkAction::make()`. Для кастомной логики задайте `handle()` (или alias `executeUsing()`). Если нужна группировка в UI — добавьте `BulkActionDropdown::make()->items([...])`.

## Полный пример

```php
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\BulkResult;

public function bulkActions(): iterable
{
    return [
        BulkAction::delete()->confirm(),

        BulkActionDropdown::make('status', 'Change status')->items([
            BulkAction::make('activate', 'Activate')
                ->confirm('Activate selected records?')
                ->allowRunByFilter()
                ->handle(function (BulkOperationContext $context): BulkResult {
                    return new BulkResult();
                }),
        ]),
    ];
}
```

## Как это работает

По умолчанию операция требует selected IDs. `allowRunByFilter()` разрешает режим «по текущему фильтру». Для пустого фильтра полный прогон остается запрещенным, пока явно не включен `allowRunWithoutFilter()`.

## Что важно учесть

- Проверяйте права на запись/удаление для каждой записи.
- Учитывайте CSRF (`sessid`) и лимиты/guard для долгих операций.
- Не включайте full-table run без явной бизнес-причины.

## Связанные разделы

- [Bulk Actions](../../bulk-actions.md)
- [Guides: Bulk actions](../guides/bulk-actions.md)
- [Reference: Actions](../reference/actions.md)
