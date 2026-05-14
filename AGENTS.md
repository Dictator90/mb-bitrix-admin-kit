# AGENTS.md

## Project conventions
- Follow PSR-12 for PHP code.
- Keep the public API backward compatible unless the task explicitly asks for a breaking change.
- Prefer extending existing resource, field, grid, and filter classes before adding new abstractions.
- Use `MB\Bitrix\AdminKit\Support\AdminCollection` instead of global collection helpers.
- Use `MB\Bitrix\AdminKit\Support\AdminString` instead of global string helpers for generated ids, aliases, keys, and HTML ids.
- For Bitrix D7 ORM features, rely on documented Bitrix ORM behavior and pass runtime field objects through to `DataManager::getList()` without embedding business-specific ORM joins in the grid layer.
- Record every user-visible change in `CHANGELOG.md`.

## Frontend conventions
- Follow BEM for markup/CSS work.
- Do not add inline `style` attributes unless there is no practical alternative.

## Testing
- Run the relevant PHPUnit tests after code changes when possible.

## v0.3.0 persistence notes
- Route CRUD persistence through `MB\Bitrix\AdminKit\Database\CrudPersister` and return `DbResult` for low-level ORM errors.
- Keep `FormData` stage-aware (`raw`, `normalized`, `validated`, `errors`) when changing form save behavior.
- Permission checks should use `MB\Bitrix\AdminKit\Security\PermissionContext` for dangerous actions.

## v0.4.0 bulk action notes
- Keep bulk operations safe by default: require explicit selected IDs unless an action opts into `allowRunByFilter()`.
- Bulk actions should return `MB\Bitrix\AdminKit\Database\BulkResult` and process records in `CrudResource::bulkChunkSize()` chunks.
- Check action `canRun()` and resource permissions (`canUpdate` / `canDelete`) per record; skipped records should not abort the whole operation.

## v0.5.0 field-layer notes
- Extend existing Field classes and adapters; do not replace the Field system or create duplicate abstractions when an existing class can be enhanced.
- Do not implement custom EntitySelector/UserSelector/iblock selector engines; wrap Bitrix `ui.entity-selector`, `BX.UI.EntitySelector.TagSelector`, and documented Bitrix providers/mechanisms.
- Keep array normalization explicit per concrete field: base/scalar fields must not implode arrays, and multiple fields should preserve arrays.
- Prefer `displayUsing()` or request-level `Database\RelationResolver` preloading for related labels to avoid N+1 queries.

## v0.6.0 diagnostics and performance notes
- Database diagnostics must stay read-only by default; do not create or alter tables from admin pages.
- Prefer `Database\Schema\TableSchema` and `SchemaAwareResource` for expected schemas instead of adding migration abstractions.
- Keep query-performance features conservative: `useTotalCount()`, count/options/lookup cache, `QueryGuard`, and `maxPageSize()` should be opt-in or safe by default.
- Generate cache keys through `AdminString::cacheKey()` and use `AdminCollection` for diagnostic, preload, debug, and batch result arrays.

## v0.7.0 import/export notes
- Keep resource import/export CSV-first; do not introduce XLSX/Excel engines without an explicit task.
- Export actions must require either explicit selected IDs or an allowed filter; full export remains disabled unless a resource/action opts in.
- Import must reuse `Form\DataPipeline` so form saves and CSV imports share Field `normalize()` and validation behavior.
- Keep import/export row sets, mappings, chunks, selected IDs, and errors on `AdminCollection` rather than global helper functions.
