# CrudResource

`CrudResource` is the recommended base class for new Bitrix D7 ORM CRUD sections. It extends `Resource`, requires `dataManagerClass()`, and inherits grid, export, and performance defaults from `Resource` without duplicating them.

`Resource` remains the backward-compatible base for legacy resources that extend it directly. For module settings, use `Pages\OptionsPage`.

## Minimal resource

```php
final class ProductResource extends DataManagerResource
{
    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function indexFields(): iterable
    {
        return [ID::make('ID'), Text::make('Name', 'NAME')];
    }

    public function formFields(): iterable
    {
        return [Text::make('Name', 'NAME')->required()];
    }
}
```

## Persistence

Create, update, delete, and bulk update/delete operations should route through `Database\CrudPersister` and return `DbResult`/`BulkResult` for low-level ORM errors. This keeps form saves, lifecycle hooks, permission checks, and transactions aligned. (CSV import persistence will use the same path when import UI is re-enabled.)

## Query hooks

Use `indexSelect()`, `indexFilter()`, `indexOrder()`, `indexRuntime()`, `modifyIndexParams()`, `afterIndexRows()`, and `mapIndexRow()` to customize the list query without changing generic grid internals.


## Bulk operation safety

Bulk actions on `CrudResource`/`DataManagerResource` remain selected-ID only by default. Use `allowRunByFilter()` on a direct `BulkAction` or a dropdown child action to enable the lower Bitrix "for all records" checkbox. When `action_all_rows_<GRID_ID>=Y` is posted, AdminKit uses the current grid filter instead of posted selected IDs.

Filter-based operations with an empty filter are full-table operations and require an explicit `allowRunWithoutFilter()` opt-in. `QueryGuard` also checks `maxBulkRows()` before materializing IDs; define `maxBulkRows(): int` on the resource to lower or raise the default limit of `5000`.
