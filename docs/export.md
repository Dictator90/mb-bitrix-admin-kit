# Resource export

v0.7.0 adds a CSV-first export layer for CRUD resources.

## Components

- `ExportAction` checks permissions and decides whether selected rows, filtered rows, or all rows may be exported.
- `ExportContext` carries the resource, selected IDs, filter, export fields, user ID, format, and optional `GridContext`.
- `CsvExporter` writes UTF-8 CSV with a BOM by default and uses safe `fputcsv()` escaping.

## Selected export

Pass explicit selected IDs in `ExportContext`. The action adds a primary-key filter and exports only those rows.

```php
$result = ExportAction::make()->execute(new ExportContext(
    resource: $resource,
    selectedIds: [1, 2, 3],
));
```

## Filter export

Pass the current filter and, when available, a `GridContext`. Export by filter is enabled by default, but a resource can disable it with `allowExportByFilter(): bool`.

```php
$result = ExportAction::make()->execute(new ExportContext(
    resource: $resource,
    filter: ['ACTIVE' => 'Y'],
    gridContext: $context,
));
```

## Full export

Exporting all records is blocked by default. Opt in explicitly on the action with `allowRunAll()` or on the resource with `allowExportAll(): bool`.

## Fields and permissions

- The resource must pass `canView()` for export.
- The action `canRun()` condition must pass.
- `CsvExporter` uses `indexFields()` unless fields are provided in the context.
- Fields hidden from the index, marked `exportable(false)`, `private()`, or `system()` are not exported.
- Computed fields and `displayUsing()` are honored in CSV output.
