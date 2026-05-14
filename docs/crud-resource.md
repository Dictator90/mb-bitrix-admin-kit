# CrudResource

`CrudResource` connects a Resource to a Bitrix D7 ORM `DataManager` and provides list, create, edit, detail, delete, bulk action, import, and export flows.

## Minimal resource

```php
final class ProductResource extends CrudResource
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

Create, update, delete, import upsert, and bulk update/delete operations should route through `Database\CrudPersister` and return `DbResult`/`BulkResult` for low-level ORM errors. This keeps form saves, import rows, lifecycle hooks, permission checks, and transactions aligned.

## Query hooks

Use `indexSelect()`, `indexFilter()`, `indexOrder()`, `indexRuntime()`, `modifyIndexParams()`, `afterIndexRows()`, and `mapIndexRow()` to customize the list query without changing generic grid internals.
