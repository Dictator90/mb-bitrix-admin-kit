# Bulk actions

Bulk actions in v0.4.0 are safe by default: they run only for explicitly selected grid IDs, validate the Bitrix sessid, process IDs in chunks, and check permissions for every row.

## BulkOperationContext

`MB\Bitrix\AdminKit\Database\BulkOperationContext` is passed to bulk action handlers and contains:

- `resource` — current resource;
- `action` — action object or action identifier;
- `selectedIds` — selected grid IDs;
- `userId` — current user ID when available;
- `request` — Bitrix request object when available;
- `filter` — prepared filter for future run-by-filter support;
- `gridContext` — current grid context when available.

## BulkResult

Bulk handlers return `MB\Bitrix\AdminKit\Database\BulkResult`. It reports:

- `total` — processed rows;
- `successCount` — successful rows;
- `failedCount` — rows with errors;
- `errorsById` — errors grouped by row ID;
- `skippedIds` — skipped row IDs with reasons;
- `message()` — user-facing summary: processed, successful, skipped, and failed counts.

## Bulk update

Use `BulkAction::update()` for simple updates or instantiate `BulkUpdateAction` directly:

```php
use MB\Bitrix\AdminKit\Action\BulkAction;

public function bulkActions(): iterable
{
    return [
        BulkAction::make('activate')
            ->label('Активировать')
            ->update(['ACTIVE' => 'Y']),
    ];
}
```

For every selected row the action loads the record, checks `canRun()`, then checks `resource->canUpdate()` with a `PermissionContext`, and finally updates the row through the CRUD persistence pipeline. One failed update is added to `BulkResult` and does not stop the remaining rows.

## Mass delete

`MassDeleteAction` performs safe mass deletion:

```php
use MB\Bitrix\AdminKit\Action\MassDeleteAction;

public function bulkActions(): iterable
{
    return [
        MassDeleteAction::make(),
    ];
}
```

The action validates sessid, rejects empty selections with `Не выбраны элементы`, loads each record, checks `canRun()` and `resource->canDelete()` per record, calls `beforeMassDelete()` / `afterMassDelete()`, deletes each row through `CrudPersister`, and stores all per-row errors in `BulkResult`.

## Callback bulk action

Use `handle()` when the operation cannot be represented as a simple update:

```php
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\BulkResult;

BulkAction::make('recalculate')
    ->label('Пересчитать')
    ->handle(function (array $ids, BulkOperationContext $context): BulkResult {
        $result = new BulkResult();

        foreach ($ids as $id) {
            // process row
            $result->addSuccess($id);
        }

        return $result;
    });
```

## Permissions and conditions

`canSee()` controls whether an action is rendered. `canRun()` is checked before the action runs and for every loaded row. Both methods accept closures, `ConditionTree`, and short `field/operator/value` conditions. Internally the package uses `AdminCondition`.

```php
BulkAction::make('activate')
    ->canRun('ACTIVE', '=', 'N')
    ->update(['ACTIVE' => 'Y']);
```

Rows that fail `canRun()`, `canUpdate()`, or `canDelete()` are added to `skippedIds`; they do not abort the operation.

## Chunk processing

Resources can tune batch size:

```php
public function bulkChunkSize(): int
{
    return 50;
}
```

The default is `100`. Bulk actions split selected IDs into chunks before processing so large explicit selections do not need to be handled as one in-memory batch.

## Run by filter warning

Running an action by filter can affect many more rows than the user can see. The API is prepared but disabled by default:

```php
BulkAction::make('activate_all_by_filter')
    ->allowRunByFilter();
```

Only explicitly selected IDs are processed unless an action opts in with `allowRunByFilter()` and the surrounding application deliberately supplies filtered IDs.
