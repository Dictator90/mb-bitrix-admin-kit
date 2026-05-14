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

## v0.8.0 page/menu/routing notes
- Keep `AdminKitManager` as the public facade and delegate page storage, routing, menu building, and rendering to focused manager classes when possible.
- Build admin URLs through `Support\UrlGenerator`; avoid manual query-string concatenation in page/menu/routing code.
- Standalone pages should preserve the legacy static API while also supporting the v0.8.0 instance API (`id`, `title`, `sort`, `icon`, `group`, `canView`, `render`, `url`).
- Centralize Bitrix UI extension loading through `Manager\AssetManager` for new page-layer work.
- Use `Manager\SidePanelAdapter` for create/edit/detail slider behavior and keep full-page mode working when `IFRAME=Y` is absent.

## v0.9.0 DX/documentation notes
- Keep examples and documentation copy-paste friendly for a standard Bitrix module layout.
- Prefer small, realistic examples over introducing new framework abstractions in DX work.
- Keep PHPStan, PHPUnit, php-cs-fixer, and CI commands documented and runnable through Composer scripts.
- Public API removals must be preceded by `@deprecated` phpdoc and documented migration notes.

## v1.0.0 stabilization notes
- Treat Resource, CrudResource, GridContext, FormData, DbResult, BulkResult, Field, Filter, Action, support adapters, persistence, grid query, URL, and import/export classes as stable public API unless a task explicitly requests a breaking change.
- Do not require userland resources to type against `MB\Support\Collection`, global helpers, or internal adapter classes; keep public extension points based on simple PHP types and callables.
- Document every backward-compatibility exception and migration step in `docs/backward-compatibility.md` and `CHANGELOG.md` before changing code.

## v1.1.0 scope/discovery notes
- Treat `scopeId` as the AdminKit area identifier; it may be a Bitrix module ID, but code must not assume it is an installed module.
- Keep `AdminKit::forModule()` module-first while supporting `forScope()`, directory shortcuts, and manual registration without discovery.
- Discovery paths must remain optional and safe: missing directories should not break manually registered resources or pages.

## v1.2.0 grid architecture notes
- Keep `GridQueryBuilder` as the only source of Bitrix ORM query params (`select`, `filter`, `order`, `runtime`, `limit`, `offset`).
- Keep `GridDataLoader` responsible for GridContext creation, QueryGuard, total count/cache, DataManager calls, and QueryPerformanceContext.
- Keep `Grid` and `src/Bitrix/Grid/*Adapter.php` focused on UI state and Bitrix component/action-panel params; do not reintroduce ORM query building into `Grid` or `IndexPage`.
- Keep toolbar/filter/create button integration in `Bitrix\Toolbar\ToolbarRenderer` when changing index-page toolbar behavior.


## v1.3.0 resource pages notes
- Treat `Resource::pages()` plus `IndexPage`, `FormPage`, and `DetailPage` subclasses as the primary view customization mechanism.
- Keep `indexFields()`, `formFields()`, and `detailFields()` as simple fallback shortcuts; do not add `indexResource()`, `formResource()`, `detailResource()`, or split Resource abstractions.
- Keep grid query/data/row layers fed by `IndexPage` definitions rather than direct `resource->indexFields()` calls when rendering an index page.
- Use `FieldRenderContext` for page-aware field rendering on index, form, and detail pages while preserving backward-compatible raw-value rendering.
- Keep standalone pages (`Pages\DashboardPage`, `Pages\OptionsPage`, `Pages\CustomPage`) registered/discovered separately from resource pages (`Page\IndexPage`, `Page\FormPage`, `Page\DetailPage`); resource page subclasses must not become standalone menu entries unless they explicitly use the standalone page API.
- Preserve the existing Field API surface and keep `renderFormField()` as the fallback used by context-aware `renderForm()` implementations.
