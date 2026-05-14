# Changelog

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
