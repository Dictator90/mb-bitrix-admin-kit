# Export (CSV)

> **Import UI is temporarily disabled** on resource index pages and toolbars. Classes under `MB\Bitrix\AdminKit\Import\*` remain in the codebase for a future release, but `ImportAction`, `ImportContext`, and `CsvImporter` are not wired into `IndexPage` in the current branch. See `docs/import.md` for library-level notes only.

AdminKit export is **CSV-first**. XLSX/Excel engines are out of scope unless a future task explicitly adds them.

## Export safety

- `ExportAction` requires explicit selected IDs or an allowed filter (`allowExportByFilter()`).
- Full export (`allowExportAll()`) stays disabled unless the Resource opts in.
- Export checks `canView()` on the Resource.
- Hidden, private, and system fields are not exported.
- Pre-flight row count runs before `getList()`; exceeding `maxExportRows()` (default `5000`) aborts with a localized error.

## Implementation

- `ExportAction` — HTTP/action entry point.
- `ExportContext` — resource, field set, filter/IDs, user context.
- `MB\Bitrix\AdminKit\Export\CsvExporter` — CSV writer (legacy `Support\Export\CsvExporter` was removed).

## Resource hooks

```php
public function allowExportByFilter(): bool { return true; }
public function allowExportAll(): bool { return false; }
public function maxExportRows(): int { return 5000; }
```

## Results

Export row sets, selected IDs, and errors use `AdminCollection` internally; public APIs expose plain arrays and iterables.
