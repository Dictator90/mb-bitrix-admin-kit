# Bulk actions (safe-by-default)

## Когда использовать

Когда нужно массовое обновление/удаление или свой пакетный action.

## Минимальный пример

```php
use MB\Bitrix\AdminKit\Action\BulkAction;

public function bulkActions(): iterable
{
    return [
        BulkAction::make('activate', 'Активировать')
            ->allowRunByFilter()
            ->executeUsing(function (array $rows): void {
                // ваш bulk flow
            }),
    ];
}
```

Правила по умолчанию:
- Требуются явные selected IDs.
- Режим “для всех” включается только через `allowRunByFilter()`.
- Пустой filter + run-by-filter требует отдельного opt-in `allowRunWithoutFilter()`.

## Ограничения

- Учитывайте `bulkChunkSize()` и `maxBulkRows()`.
- Для dropdown используйте placeholder как первый неисполняемый элемент (`Types::DROPDOWN`).

## См. также

- [Reference: Actions](../reference/actions.md)
- [Permissions](permissions.md)
