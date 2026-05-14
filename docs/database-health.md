# Database health and schema diagnostics

v0.6.0 adds read-only database diagnostics for CRUD resources. Diagnostics never create tables,
add columns, add indexes, or run migrations from the admin UI.

## Declare expected schema

Resources that need diagnostics may implement `SchemaAwareResource`:

```php
use MB\Bitrix\AdminKit\Database\Schema\TableSchema;
use MB\Bitrix\AdminKit\Resource\SchemaAwareResource;

final class ProductResource extends CrudResource implements SchemaAwareResource
{
    public function expectedTableSchema(): TableSchema
    {
        return TableSchema::make('vendor_product')
            ->column('ID', 'int', required: true)
            ->column('NAME', 'string', required: true)
            ->column('ACTIVE', 'string', required: true)
            ->index('PRIMARY', ['ID'])
            ->index('IX_ACTIVE', ['ACTIVE']);
    }
}
```

`CrudResource::databaseTableName()` resolves the table from `DataManager::getTableName()` when the ORM class exposes it.

## Inspect and check tables

`DatabaseSchemaInspector` uses the Bitrix connection to check:

- table existence;
- columns;
- indexes.

`TableHealthCheck` compares the declared `TableSchema` with the live database and reports:

- missing table;
- missing required columns;
- missing indexes;
- safe basic type mismatches when a type can be determined.

## Optional health page

`MB\Bitrix\AdminKit\Page\System\DatabaseHealthPage` accepts an iterable of resources and renders a diagnostic table with resource id, DataManager class, table name, missing columns, missing indexes, and status.

The page is intentionally optional. Register it only in tools or admin sections where this is appropriate for privileged users.
