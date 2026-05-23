# Reference: Actions

## Классы

- Row-level: `RowAction`
- Bulk-level: `BulkAction`, `BulkActionDropdown`, `BulkUpdateAction`, `MassDeleteAction`
- Export/Import: `Export\ExportAction`, `Import\ImportAction`
- Async: `AsyncAction`

## Bulk contracts

- Вход: `BulkOperationContext`
- Результат: `BulkResult`
- Обработка чанками: `bulkChunkSize()`

## AsyncAction

`AsyncAction` задает JSON endpoint:
- `handle(array $data): array`
- `dispatch(HttpRequest $request): void` с CSRF-проверкой `sessid`
- helper-методы: `sendSuccess()`, `sendError()`

## Ограничения

- Запуск “для всех” — только явный opt-in через `allowRunByFilter()`.
- Full export запрещен по умолчанию (если не включен `allowExportAll()`).
