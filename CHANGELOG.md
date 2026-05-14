# Changelog

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
