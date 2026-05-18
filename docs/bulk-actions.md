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

`BulkAction::delete()` creates a `MassDeleteAction` with danger-group UI defaults. `MassDeleteAction` performs safe mass deletion:

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

`handle()` и совместимый alias `executeUsing()` регистрируют callback. Callback должен возвращать `BulkResult`; это позволяет UI показать частичные ошибки, skipped-записи и affected count.


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

## UI and Action Panel grouping

Bulk actions can be grouped, sorted, and styled for the Bitrix `main.ui.grid` action panel.

```php
use MB\Bitrix\AdminKit\Action\BulkAction;

public function bulkActions(): iterable
{
    return [
        BulkAction::make('activate', 'Активировать')
            ->group('status', 'Статус')
            ->icon('ui-btn-icon-success')
            ->sort(10)
            ->allowRunByFilter()
            ->handle(fn(array $ids, BulkOperationContext $context) => ...),

        BulkAction::make('deactivate', 'Деактивировать')
            ->group('status', 'Статус')
            ->icon('ui-btn-icon-stop')
            ->sort(20)
            ->allowRunByFilter()
            ->handle(fn(array $ids, BulkOperationContext $context) => ...),

        BulkAction::delete()
            ->group('danger', 'Удаление')
            ->sort(100),
    ];
}
```

### UI methods

- `group(string $id, ?string $label = null, ?int $sort = null)` — sets the group ID, optional label, and optional group sort value. Bitrix action panel groups items visually.
- `groupSort(int $sort)` — controls ordering between groups; groups are sorted by `groupSort`, then by group key.
- `sort(int $sort)` — sets the display order within the group (default `100`).
- `icon(string $class)` — adds a Bitrix CSS icon class (e.g., `ui-btn-icon-remove`, `ui-btn-icon-success`).
- `buttonClass(string $class)` or `class(string $class)` — adds custom CSS classes to the button.
- `title(string $title)` — sets the button's `title` attribute.
- `danger(bool $danger = true)` — marks the action as dangerous. In Bitrix UI, this automatically adds the `ui-btn-danger` class if no other button class is specified.
- `confirm(string $message)` — shows a Bitrix confirmation dialog before running the action.
- `panelType(string $type)` — sets the Bitrix panel type (default `BUTTON`).
- `panelItem(array|Closure $item)` — provides a raw Bitrix action panel item array. If a closure is used, it receives the `Grid` instance.

### Select all records / For all mode

If at least one direct bulk action or dropdown child action has `allowRunByFilter()` enabled, the grid automatically shows Bitrix `SHOW_SELECT_ALL_RECORDS_CHECKBOX` — the lower action-panel checkbox for "all records". This is not the header checkbox that selects visible rows.

When that checkbox sends `action_all_rows_<GRID_ID>=Y`, AdminKit treats the operation as filter-based even if selected IDs are also posted by the browser. The backend ignores selected IDs in this mode and uses the current grid filter.

```php
BulkAction::make('activate', 'Активировать')
    ->allowRunByFilter()
    ->update(['ACTIVE' => 'Y']);
```

Running a filter-based action with an empty filter means "all rows" and is blocked by default. Enable it only for actions that are safe for a full-table operation:

```php
BulkAction::make('activate_all', 'Активировать все')
    ->allowRunByFilter()
    ->allowRunWithoutFilter()
    ->update(['ACTIVE' => 'Y']);
```

`QueryGuard` counts affected rows before IDs are materialized and blocks operations above `maxBulkRows()` (default `5000`; add `maxBulkRows(): int` on the resource to customize). You can override checkbox visibility on the Grid:

```php
$grid->showSelectAllRecordsCheckbox(false);
```

### Dropdown bulk actions

You can group multiple bulk actions into a dropdown menu to save space in the action panel. `BulkActionDropdown` acts as a UI container; the actual execution is performed by the selected child action.

```php
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;

public function bulkActions(): iterable
{
    return [
        BulkActionDropdown::make('activity', 'Активность')
            ->group('status', 'Статус')
            ->sort(10)
            ->items([
                BulkAction::make('activate', 'Активировать')
                    ->allowRunByFilter()
                    ->update(['ACTIVE' => 'Y']),

                BulkAction::make('deactivate', 'Деактивировать')
                    ->allowRunByFilter()
                    ->update(['ACTIVE' => 'N']),
            ]),

        BulkAction::delete()
            ->group('danger', 'Удаление')
            ->sort(100),
    ];
}
```

- **Container Only**: Dropdowns themselves do not have handlers or update data.
- **Placeholder**: Bitrix `Types::DROPDOWN` displays the first/selected item as the visible label. AdminKit therefore inserts a placeholder item first; the dropdown label is used as that placeholder by default.
- **Placeholder API**: Use `->placeholder('Выберите действие')` to change the placeholder or `->withoutPlaceholder()` to render only executable child items.
- **Unique IDs**: Child action IDs must be unique across all bulk actions in the grid. The placeholder is not executable and does not participate in ID validation.
- **Run by filter**: `allowRunByFilter()` should be set on the child actions. If any child action (or direct action) supports running by filter, the "Select all records" checkbox will appear.
- **Multiple mode**: `multiple(true)` is intentionally rejected until backend execution supports multiple selected dropdown child actions.

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


## Custom client handler

По умолчанию action panel вызывает `kit.GridBulkActions.runBulkAction(config)`. Если действию нужен другой client-side flow, задайте metadata на action, не проверяя конкретный id в адаптере:

```php
BulkAction::make('export_csv', 'Export CSV')
    ->clientHandler('exportSelected');
```

Handler должен быть функцией в namespace `GridBulkActions` extension `mb.admin.kit`. Небезопасное имя handler автоматически откатывается к `runBulkAction`.

## User-visible results

`BulkResult::toArray()` отдаёт `success`, `status`, `message`, `summary`, `errors`, `warnings`, `skipped`, `affected` и `successfulIds`. AJAX flow показывает ошибки сразу через `ui.notification`, затем обновляет таблицу. Non-AJAX fallback сохраняет тот же payload во flash session и рендерит `ui.alerts` на следующей загрузке.
