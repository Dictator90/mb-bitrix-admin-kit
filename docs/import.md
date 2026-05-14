# Resource import

v0.7.0 adds a CSV-first import layer for CRUD resources. XLSX/Excel import is intentionally out of scope for this version.

## Components

- `ImportAction` exposes parse, preview, validate-only, and import flows.
- `ImportContext` carries the resource, raw rows, mapped rows, user ID, mode, request, key field, row limit, and validate-only flag.
- `CsvImporter` parses header-based CSV files, maps columns to resource fields, validates rows, and persists rows.

## CSV format

The first row is treated as headers. Values are mapped to resource field columns before validation/import.

```csv
Name,Email
Product 1,owner@example.com
```

## Preview and validate-only

Use `preview()` to parse, map, and validate without writing data. Use `validateOnly` on `ImportContext` or `validateOnly()` on the action to run validation without persistence.

## Modes

- `create`: creates every valid row.
- `update`: updates rows by the configured `keyField`.
- `upsert`: updates when `keyField` exists and creates otherwise.

For `update` and `upsert`, provide a key field such as `ID` or an external code.

## Field pipeline

Import uses `Form\DataPipeline`, which calls each Field's `normalize()` and validation rules. This keeps form saves and CSV imports consistent.

## Permissions and limits

- Create imports require `canCreate()`.
- Update imports require `canUpdate()`.
- Each row is checked before persistence.
- Imports are limited to `ImportContext::$maxRows`; resources can further cap this with `maxImportRows()`.
- Fields hidden from forms, marked `importable(false)`, `private()`, or `system()` are ignored.
