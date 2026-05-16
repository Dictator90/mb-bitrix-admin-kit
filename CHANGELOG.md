# Changelog

## Unreleased

### Added
- `UI\EntitySelector\IblockSectionListProvider` for `iblock-section-list` entity (`IblockSectionSelect`, `EntitySelect::entityId('iblock-section-list')`).
- Grouped index rows via `Grid\Grouping\IndexGrouping`, `IndexPage::grouping()`, resource `indexGrouping()`, Bitrix collapsible grid row metadata, and synthetic group/item row IDs.
- Relation index fields `Field\HasMany` and `Field\HasOne` that batch-load related values without joining or duplicating base grid rows.
- Field edit links for index columns through `asEditLink()` / `linkToEdit()` with SidePanel-aware edit URLs.
- `Discovery\ClassDiscovery` — registry discovery isolated from `AdminKitRegistry`, backed by `mb4it/filesystem` `ClassFinder` and Reflection-based final-class checks.
- `Resource::pages()` with default `IndexPage`, `FormPage`, `DetailPage`; `PageContract`, `PageFactory`, `ResourcePageResolver`, `PageNotFoundException`.
- `FieldRenderContext` for page-aware index/form/detail field rendering with backward-compatible raw-value rendering.
- `Resource::maxExportRows()` (default `5000`) and pre-flight export row counting in `ExportAction`.
- Narrow resource contracts (`ResourceIdentityContract`, `ResourceMenuContract`, `ResourcePermissionContract`, `OrmResourceContract`, `IndexResourceContract`, `FormResourceContract`, `DetailResourceContract`, `ExportableResourceContract`, `ExportResourceContract`); aggregate `ResourceContract` unchanged for BC.
- `mb.admin.kit` Bitrix extension: `MB.AdminKit.Form`, `.Dependencies`, `.Visibility`, `.OptionsPage` (form save, field dependencies, visibility, options page).
- Page/security/export tests: `PageFactoryTest`, `ResourcePageResolverStrictTest`, `IndexPageSecurityTest`, `DetailPagePermissionTest`, `FormPageValidationLifecycleTest`, `FormPageSidePanelAsyncTest`, `ImportRemovedFromIndexPageTest`, `OptionsPageBackwardCompatibilityTest`, `FormPageJsExtensionTest`, `OptionsPageStabilizationTest`, grid and discovery coverage.

### Changed
- Grouped index grids initialize collapsible rows via `AdminKitJs::renderInit('GridCollapsible')` on `IndexPage` instead of auto-starting from `mb.admin.kit` bundle entry.
- `Resource` — BC base (identity, menu, permissions, pages, export defaults); `CrudResource` — thin ORM layer (`dataManagerClass()`, `hasCrud(): true`) without duplicated defaults.
- Grid split: `GridQueryBuilder` (ORM params only), `GridDataLoader` (load/count/cache), `Grid` + Bitrix adapters (UI); `IndexPage` delegates to these services.
- `FormPage` / `DetailPage` — page-level `fields()` / `tabs()` are primary; resource shortcuts are fallbacks.
- `Pages\OptionsPage` stabilized (sessid, JSON array options, `visibleWhen` aligned with `FormPage`); `Page\OptionsPage` deprecated wrapper retained.
- Routing: `admin_resource` + `admin_page` alongside legacy `page` / `action`.
- Export: removed legacy `Support\Export\CsvExporter`; use `Export\CsvExporter` + `ExportAction`.
- Discovery: multi-path, safe missing directories, duplicate-id preservation; standalone pages via `AbstractPage::isStandalone()`.
- Documentation: README, `docs/pages.md`, `docs/grid.md`, `docs/discovery.md`, `docs/architecture.md`, `docs/upgrade.md`, export-only `docs/import-export.md`, import-disabled `docs/import.md`.

### Removed / disabled
- Import UI and toolbar entrypoints removed from `IndexPage` (no `action=import`, no import SidePanel flow on index). Library `Import\*` classes remain for future re-enable.

### Fixed
- CSRF: POST saves and options updates require valid sessid; AJAX returns JSON errors, normal POST shows alert.
- `DetailPage` enforces `canView` before rendering a record.
- `FormPage` validation/save lifecycle and permission checks (`canCreate` / `canUpdate`) on render and save.
- `FormPage` async SidePanel save returns JSON via `sendAsyncSaveResponse()` instead of full-page redirect.
- String primary-key delete on index grid.
- `GridQueryBuilder::buildOrder()` three-layer sort merge.
- Export guard messages localized; export failures use `ui.alerts` on index.
- Form/toolbar RU label mojibake (UTF-8 `Loc` files).

## v1.0.0 - 2026-05-14

### Changed
- Split Bitrix grid architecture into `GridQueryBuilder` for ORM params, `GridDataLoader` for DataManager loading/count/cache, `Grid` for state, Bitrix grid/filter/action-panel adapters for component params, and `ToolbarRenderer` for toolbar/filter/create integration.
- Removed ORM query construction from `Grid` and made `IndexPage` delegate data loading and toolbar rendering to dedicated services.
- Documented the new grid layering in `docs/grid.md` and added coverage for the new query/data/UI boundaries.

### Added
- Added scoped AdminKit creation with `AdminKitScope`, `forModule()`, `forScope()`, `fromDirectory()`, and `fromDirectories()` for module, `local/php_interface`, custom-directory, and manual-registration workflows.
- Added multi-path discovery configuration and registry discovery that safely ignores missing paths and skips abstract classes.
- Documented scope-based discovery in README and `docs/discovery.md`.

### Added
- Added the `MB\Bitrix\AdminKit\AdminKit` facade for creating per-module managers.
- Documented the v1.0.0 stable public API review scope for Resources, CRUD resources, grid/form contexts, database result objects, fields, filters, actions, support adapters, URL generation, and import/export.
- Added a backward-compatibility policy for v1.x covering public/protected method signatures, class names, namespaces, CRUD behavior, `FormData`, `GridContext`, `DbResult`, `BulkResult`, and base Field/Filter/Action APIs.
- Added v1.0.0 documentation pages for installation, resources, CRUD resources, database integration, grid, filters, forms, actions, lifecycle, import/export, and backward compatibility.
- Added compatibility coverage for stable public class loading and avoiding direct global support helper declarations/calls.
- Added focused v1.0.0 examples for simple CRUD, product resources, runtime fields, computed columns, bulk actions, Bitrix field adapters, database health, and CSV import/export.
- Added a root `phpstan.neon` entrypoint and pointed the Composer analysis script at it while keeping the level 6 configuration in `phpstan.neon.dist`.
- Added v1.0.0 agent notes to preserve stable public APIs, support package adapter boundaries, and migration documentation.

### Changed
- Expanded the README with stable API, lifecycle, transaction, permission, database health, performance, Bitrix UI adapter, documentation, and examples guidance for v1.0.0.
- Confirmed Composer runtime requirements stay on PHP `^8.2` and support packages `mb4it/collections`, `mb4it/stringable`, and `mb4it/conditionable` `^1.0`.
- Restored legacy selector aliases (`UserSelect`, `EntitySelect`, `IblockElementSelect`) as first-class adapters rendered through `mb.ui.dialog-selector`/`MB.UI.DialogSelector`, while keeping `*SelectorField` classes available.
- Reworked bulk action panel execution to use native `main.ui.grid` `reloadTable('POST', ...)` flow (with `action_button_{GRID_ID}` and `action_all_rows_{GRID_ID}`), while keeping legacy `adminkit_bulk_action` JSON handling for backward compatibility.
- Hardened server-side bulk request parsing for `action_button_{GRID_ID}`, `controls[group_action]`, and multiple selected-ID keys (`id`, `ID`, `rows`, primary-key aliases).
- Added row action SidePanel close handlers that reload the bound grid after edit/view sliders close.
- Added native inline grid editing support: enabled `ALLOW_INLINE_EDIT`/`ALLOW_EDIT_SELECTION` for editable fields and handled `action_button_{GRID_ID}=edit` row saves through `DataPipeline` and CRUD persistence.
- Simplified selector field rendering by unifying `EntitySelectorField` form output through the `mb.ui.dialog-selector` path used by selector aliases, reducing dual-engine complexity.
- Replaced `*SelectorField` selector classes with `*Select` classes (`EntitySelect`, `DialogSelect`, `TagSelect`, `UserSelect`, `IblockElementSelect`, `IblockSectionSelect`) and removed old `*SelectorField` classes.
- Updated `EntitySelect` label resolution to use `src/UI/EntitySelector` providers by default; `resolveLabels()` now serves as an optional custom-label override.
- Improved `IblockElementListProvider` avatar resolution for selector items: now uses `PREVIEW_PICTURE`, then `DETAIL_PICTURE`, then `MORE_PHOTO` fallback.
- Restored legacy `IblockElementSelect` entity binding to `iblock-element-list` and legacy label resolution behavior from previous support package implementation.
- Added toolbar buttons for CSV export/import and wired `action=export`/`action=import` handling in `IndexPage` to execute `ExportAction` and `ImportAction`.
- Updated import UX: `action=import` now opens in SidePanel and uses AdminKit `ui-form`/Field-based layout instead of a raw standalone markup block.
- Reworked CSV import UI to a staged flow using Bitrix `ui.stepprocessing` (`parse` -> `validate` -> `import`) inside SidePanel and removed the `К списку` action from the import form.
- Temporarily removed import entrypoints from resource index pages and toolbar, and added configurable resource toolbar actions with built-in `export` action support.
- Localized export guard/error messages via Bitrix `Loc` and ensured export failures render with styled `ui.alerts` notifications on index pages.
- Localized index and toolbar user-facing labels/messages (create/export button labels, inline row error template, and export fallback message) via Bitrix `Loc` keys.
- Unified toolbar management across `IndexPage`, `FormPage`, and `DetailPage` using Bitrix `Toolbar` facade patterns (top save/cancel on form, back/edit on detail, and localized toolbar labels).

### Migration notes
- v1.0.0 is a stabilization release, not a feature expansion release. Existing v0.1.0-v0.9.0 Resource, Field, Filter, Action, persistence, bulk action, import/export, and page-layer extension points remain the migration path.
- Userland code should depend on `MB\Bitrix\AdminKit\Support\AdminCollection`, `AdminString`, and `AdminCondition` only when an adapter is actually needed; public Resource APIs should continue to expose plain PHP values.

### Known limitations
- Import/export remains CSV-first.
- Database health pages are diagnostic/read-only by default and do not replace migrations.
- Bitrix UI selector fields wrap Bitrix selector assets and providers; they do not provide a custom selector engine.

## v0.9.0 - 2026-05-14

### Added
- Rewrote README as a complete DX guide covering installation, Bitrix module wiring, Resources, CRUD pages, fields, filters, actions, OptionsPage, CustomPage, SidePanel, permissions, ORM query customization, runtime fields, computed columns, import/export, and support adapters.
- Added `docs/quick-start.md` with a minimal end-to-end Bitrix module flow: ORM table, ProductResource, admin file, menu, create, edit, and delete.
- Added `examples/demo-module` with a realistic Bitrix module skeleton demonstrating Product CRUD, Text/Select/Switcher fields, TextFilter/SelectFilter, computed columns, runtime fields, row actions, bulk actions, OptionsPage, DashboardPage, and SidePanel create/edit.
- Added cookbook recipes for fields, filters, row actions, bulk actions, SidePanel, runtime ReferenceField, computed columns, custom save, lifecycle hooks, permissions, settings pages, dashboards, EntitySelectorField, and import/export.
- Added architecture and support-package documentation plus upgrade/deprecation notes.
- Added PHPStan level 6 configuration, php-cs-fixer configuration, `composer cs-check`, and GitHub Actions CI for composer validate/install/test/analyse/code-style checks.
- Added v0.9.0 agent notes to keep future DX work copy-paste friendly and backward compatible.

### Changed
- Stabilized Composer QA scripts around `composer test`, `composer analyse`, `composer cs-fix`, and dry-run `composer cs-check`.
- Documented the deprecation policy: no public API removals without `@deprecated` phpdoc and migration notes.

## v0.8.0 - 2026-05-14

### Added
- Added a backward-compatible manager split: `AdminKitRegistry`, `AdminKitRouter`, `AdminKitMenuBuilder`, and `AdminKitRenderer`.
- Stabilized standalone page API with instance `id`, `title`, `sort`, `icon`, `group`, `canView`, `render`, and `url` methods while keeping legacy static methods.
- Added centralized URL routing helpers for CRUD pages, standalone pages, action endpoints, bulk actions, and import/export endpoints.
- Added `AssetManager`, `ToolbarAction`, `SidePanelAdapter`, and `DashboardPage`.
- Extended resources with SidePanel settings and menu grouping helpers.
- Added the simple `OptionsPage::fields()` API while preserving component-based options pages.
- Documented pages, routing, menus, options pages, custom pages, dashboards, SidePanel, toolbars, and assets.
- Added v0.8.0 tests for registry, routing, menus, page API, options/custom/dashboard pages, SidePanel, toolbar, assets, permissions, and URL routing.


## v0.7.0 - 2026-05-14

### Added
- Added CSV-first resource export with `ExportAction`, `ExportContext`, `ExportResult`, `ExporterInterface`, and `CsvExporter`.
- Added selected-record export and filter-based export while keeping full export disabled by default.
- Added export permission checks and field visibility/private/system guards so hidden or private fields are not exported.
- Added CSV import with `ImportAction`, `ImportContext`, `ImportResult`, `ImporterInterface`, and `CsvImporter`.
- Added import preview/validate-only support, column mapping, create/update/upsert modes, configurable key field, and row limits.
- Added shared `Form\DataPipeline` so form saves and imports use the same Field normalization and validation pipeline.
- Added documentation for CSV import/export formats, permissions, limits, selected exports, filter exports, preview, validate-only, and import modes.

### Changed
- Form saves now route Field normalization and validation through the shared data pipeline used by CSV imports.

## v0.6.0 - 2026-05-14

### Added
- Added read-only database schema diagnostics with `DatabaseSchemaInspector`, `TableSchema`, `TableHealthCheck`, and optional `DatabaseHealthPage`.
- Added `SchemaAwareResource` and `CrudResource::databaseTableName()` for declaring and discovering expected resource tables.
- Added query performance controls with `QueryPerformanceContext`, `QueryGuard`, `useTotalCount()`, `countCacheTtl()`, and `maxPageSize()`.
- Added TTL caching for grid counts, `Select` options, and relation lookups through request-level and optional persistent caches.
- Added documentation for database health, schema diagnostics, disabling count, count/options/lookup cache, query guard, and max page size.
- Added PHPUnit coverage for v0.6.0 schema diagnostics, health page diagnostics, count disabling/cache, options cache, lookup cache, query guard, max page size, and cache key generation.

### Changed
- Grid loading now caps requested page size using the resource `maxPageSize()` before ORM parameters are executed.
- Bulk action execution now validates selected IDs and run-by-filter safety through `QueryGuard`.

## v0.5.0 - 2026-05-14

### Added
- Added a unified Field API for index/form/detail rendering, normalization, conditional required/readonly/visible behavior, dependencies, placeholders, help text, defaults, and `displayUsing()` presentation callbacks.
- Added Bitrix UI selector adapters: `EntitySelectorField`, `UserSelectorField`, `IblockElementSelectorField`, and `IblockSectionSelectorField`, while keeping legacy selector class names available.
- Added `UfField` as an adapter over Bitrix user-field metadata and rendering.
- Added `RelationResolver` for batched request-level relation lookup caching to avoid N+1 display queries.
- Added field compatibility tests for callable options, multiple normalization, conditional behavior, selector normalization, display callbacks, and relation preloading.
- Added field documentation for the common Field API, concrete fields, Bitrix UI selector adapters, UF fields, normalization, validation, conditions, dependencies, and lookup preloading.

### Changed
- `Select` now supports callable options, correct single/multiple rendering, label rendering in index/detail views, required validation, and array-safe multiple normalization without comma implosion.
- Required validation now treats empty arrays as empty values for multiple fields.
- `Number` now normalizes empty input to `null` and numeric input to `int` or `float`.

## v0.4.0 - 2026-05-14

### Added
- Added safe bulk operation infrastructure with `BulkOperationContext`, `BulkResult`, chunked selected-ID processing, per-row permission checks, and user-facing operation summaries.
- Added fluent bulk action APIs for labels, confirmation, danger styling, visibility/run conditions, callback handlers, simple bulk updates, and opt-in run-by-filter support.
- Added `MassDeleteAction` and `BulkUpdateAction` for safe mass delete and bulk update operations through the existing CRUD persistence pipeline.
- Added `bulkChunkSize()` to resources with a default chunk size of 100.
- Added bulk action documentation covering mass delete, bulk update, callback actions, permissions, chunk processing, and run-by-filter warnings.
- Added PHPUnit coverage for bulk results, empty selections, mass delete, bulk update, callback handlers, permissions, chunk processing, and `canRun` conditions.

### Changed
- Grid action panels now submit every configured bulk action through the same safe execution path instead of special-casing only bulk delete.

## v0.3.0 - 2026-05-14

### Added
- Added `DbOperationContext`, `DbResult`, `CrudPersister`, and `TransactionManager` for centralized ORM persistence and transactional CRUD operations.
- Added explicit `FormData` stages, field-level validation errors, conditional field helpers, lifecycle hooks, Bitrix CRUD events, and permission contexts.
- Added documentation for the saving pipeline, FormData stages, CrudPersister, transactions, lifecycle hooks, permissions, conditional validation, and ORM errors.
- Added PHPUnit coverage for persistence results, CRUD persisting, transactions, lifecycle hooks, FormData stages, conditional validation, and permissions.

### Changed
- CRUD create, update, delete, and mass delete now use the shared persister and transaction flow while preserving legacy lifecycle hook methods.
- Form saves now surface Bitrix ORM errors without treating failed `Result` objects as successful operations.

## v0.2.0 - 2026-05-13

### Added
- Added index query extension hooks to CRUD resources: `indexSelect`, `indexFilter`, `indexOrder`, `indexRuntime`, `beforeIndexQueryParams`, `afterIndexRows`, and `mapIndexRow`.
- Added ORM runtime field support for grids, including pass-through support for Bitrix `ReferenceField` instances.
- Added computed grid/detail fields via `Field::computed()` without automatically selecting computed columns from ORM.
- Added `Field::displayUsing()` for grid/detail presentation callbacks.
- Added filter ORM application API through `applyToOrmFilter()`.
- Added `CallbackFilter` for fully custom ORM filter logic.
- Added filter operators for text, number, select, and date filters.
- Added tests for query select/filter/order/runtime assembly, computed columns, display callbacks, callback filters, empty values, and row mapping.

### Changed
- Grid ORM query building now merges index field select, default/index select, UI/default/index filters, UI/default/index order, runtime fields, pagination, and resource parameter hooks in a deterministic order while preserving v0.1.0 hooks.
- Grid rows are now post-processed through resource row hooks before computed/display values are assembled.
- Empty filter values skip ORM filtering while preserving meaningful values like `0`, `'0'`, and `false`.


## v0.1.0 - 2026-05-13

### Added
- Added the initial Resource/CRUD skeleton for Bitrix admin pages.
- Added base Field, Filter, Action, Grid, and Page abstractions.
- Added initial README and PHPUnit smoke coverage for early CRUD/grid behavior.
