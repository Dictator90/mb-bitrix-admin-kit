# Changelog

## Unreleased

### Fixed
- Grid inline-edit metadata now respects field readonly state: `readonly()` fields no longer publish editable config into `main.ui.grid` columns.
- Relation and entity selector fields are now explicitly non-inline-editable in grid metadata to avoid unstable runtime editors and enforce form/sidepanel editing flows.
- `Select` grid column metadata now exports editable `items` only when inline editing is actually enabled for the column.
- `BelongsTo` (and `BelongsToMany`) grid metadata now explicitly disables inline editing, aligning relation-like selects with stable sidepanel/form editing flows and fixing failing tests/CI checks.
- Fixed local CI failures by removing the debug-only Bitrix `Debug::writeToFile()` call from mass delete, restoring the `BulkAction::executeUsing()` callback alias, and adding missing PHPStan Bitrix stubs.
- Bulk action AJAX payloads now include structured `status`, `errors`, `warnings`, `affected`, and summary data; non-AJAX flash rendering includes item-level errors/warnings so messages survive a reload.
- `BitrixGridActionPanelAdapter` no longer chooses the export JavaScript handler by the magic `export_selected` id; bulk actions can now declare a `clientHandler()`.
- Composer package stability is stable by default; the package no longer opts consumers into dev dependency resolution.

### Documentation
- Documented field render lifecycle and inline-edit compatibility/limitations for base, select, relation, and entity selector fields.
- Clarified that `CrudResource` is a DSL/page base without persistence and that ORM CRUD resources should extend `DataManagerResource`.
- Synchronized import/export, grid, quick-start, and bulk-action docs with the current export-enabled/import-UI-disabled state.

### Stabilization
- Test runner no longer aborts silently on JSON/early-response branches: response termination is centralized and test-aware (`Support\ResponseTerminator`), so `composer test` always returns a full summary.
- Restored legacy `Resource` behavior expected by v1 modules: default CRUD page helpers (`indexPage/formPage/detailPage`), menu/permission/grid defaults, and DataManager fallback compatibility for direct `Resource` + persistence-trait usage.
- Restored legacy page aliases `Page\IndexPage`, `Page\FormPage`, `Page\DetailPage` as compatibility wrappers over `Page\Crud\*` (deprecated wrappers retained).
- Fixed standalone-page registration safety (`AdminKitRegistry` now validates type before calling standalone methods).
- Fixed menu builder safety for non-CRUD resources (guarded calls to optional `canView`/`hasCrud`/`group`).
- Fixed field display pipeline double-formatting (`displayUsing`/preview no longer double-applies formatting).
- Fixed `UserListProvider` boolean filter conditions (`onlyWithEmail`, `invitedUsers`).
- Fixed selected export filtering to use primary-key filtering compatible with existing resource expectations.
- Added inline-edit BC bridge on `IndexPage::saveInlineRow()` and visibility-rule BC helper on `Pages\OptionsPage::checkVisibilityRule()`.
- Updated JS extension tests/fixtures to current `mb.admin.kit` bundle layout and stabilized isolated test fixtures.
- Refreshed PHPStan baseline (`phpstan-baseline.neon`) to separate current known issues from new regressions.

### Removed
- Unused legacy field traits in `Field\Traits\*` (`HasValidation`, `HasFormat`, `HasVisibility`, `HasReactivity`, `Makeable`); use `Field\Concerns\*` instead.
- Unused `Resource\Concerns\HasResourcePages` trait (superseded by `HasCrudResourcePages`).
- Standalone Bitrix JS extensions `mb.ui.tabs` and `mb.ui.dialog-selector`; Tabs and DialogSelector runtime now ship inside `mb.admin.kit`.
- Legacy contract aliases in `MB\Bitrix\AdminKit\Contracts\` (`IndexResourceContract`, `FormResourceContract`, `OrmResourceContract`, `ExportResourceContract`, and others) — use `Contracts\Resource\*` instead.
- Legacy resource traits `HasCrud`, `HasPermissions`, `HasLifecycleEvents` — use `Resource\Concerns\*` instead.
- Deprecated `Page\OptionsPage` wrapper — use `Pages\OptionsPage`.
- Deprecated page aliases: `Pages\AbstractPage`, `Pages\CustomPage`, `Pages\DashboardPage`, `Page\IndexPage`, `Page\FormPage`, `Page\DetailPage`, `Contracts\PageContract` — use `Page\StandalonePage`, `Page\Standalone\*`, `Page\Crud\*`, and `Contracts\Page\*` instead.
- Unused grid panel classes `Grid\Panel\PanelDataProvider`, `Grid\Panel\BulkDeletePanelAction`.
- `AdminKitJs::renderGridCollapsibleInitialState()`, `Tab::getFields()`, `HasResourceExport::maxImportRows()`.
- Wide contracts `Contracts\FieldContract` and `Contracts\ComponentContract`; use `Contracts\Field\*`, `Contracts\UI\*`, and `Contracts\Widget\*`.

### Added
- Added `AdminKitScope::fromModuleId()` and `resolveModulePath()` for module-relative resource/page discovery without replacing explicit `Loader::includeModule()` bootstrap.
- `Support\LocalizedMessage` — shared `Loc::getMessage` helper for user-facing strings (replaces duplicated private `message()` methods).
- `Pages\Handlers\OptionsPagePostHandler` and `Pages\Handlers\OptionsPageFormRenderer` — extracted POST and form rendering from `Pages\OptionsPage`.
- `Tabs::remember()` — stores the last active tab in session (per page id) and restores it on the next visit; hidden `adminkit_active_tab` field syncs tab clicks when remember is enabled.
- `Field::preserveStoredValueWhenEmpty()` — used by `Password` so an empty submit keeps the stored option value instead of deleting it.
- `Password::oldValue()` (default `true`) — shows the stored value with a show/hide toggle; `oldValue(false)` keeps the previous empty-field edit behavior.
- New Resource architecture: `Resource` (core) -> `CrudResource` (DSL) -> `DataManagerResource` (ORM).
- Logic extracted into reusable concerns: `HasResourceIdentity`, `HasResourceMenu`, `HasResourcePages`, `HasResourceFields`, `HasResourceFilters`, `HasResourceActions`, `HasResourceAuthorization`, `HasResourceSidePanel`, `HasResourceGrid`, `HasResourceQuery`, `HasResourceGrouping`, `HasResourceExport`, `HasResourceLifecycle`, `HasDataManager`, `HasDataManagerPersistence`.
- Narrow resource contracts in `Contracts\Resource\*` for better dependency management.
- `DataManagerResource` as the preferred base class for ORM-backed resources.
- `ResourceActionsContract` with `hasAction()` and `activeActions()` for granular action control.
- `RelationField` is now `readonly(true)` by default to prevent accidental saving of relation data.
- CSRF protection for all bulk actions (added `sessid` to action panel JS).
- New UI contracts: `Contracts\UI\RenderableContract`, `ComponentContract`, `LayoutComponentContract`, `FieldContainerContract`, `ItemAwareContract`, `PageTypeAwareContract`, `HtmlAttributesContract`, `ConditionalVisibilityContract`, `AssetAwareContract`.
- New field contracts in `Contracts\Field\*` with aggregate `Contracts\Field\FieldContract`.
- New renderers: `Field\Renderers\FieldRowRenderer`, `Component\Renderers\ChildrenRenderer`, `Component\Renderers\VisibilityWrapper`, `Widget\Dashboard\DashboardRenderer`.
- New options resolver stack for `Select`: `Field\Options\*Resolver`.
- New chart architecture: `Widget\ChartWidget` + `Widget\Renderers\ChartWidgetRenderer` (`GraphWidget` now compatibility alias).
- `BulkActionDropdown::placeholder()`, `withoutPlaceholder()`, and placeholder accessors for controlling the first Bitrix dropdown item.
- `BulkAction::allowRunWithoutFilter()` for explicitly allowing guarded full-table bulk actions, plus `BulkOperationContext::$forAll`.
- Bulk action `groupSort()` support for deterministic action-panel group ordering.

### Changed
- Updated bootstrap/discovery documentation and demo module examples to separate Bitrix-module and local-admin usage scenarios.
- `AdminKitScope::fromModule(string)` now treats string values as Bitrix module IDs and delegates to `fromModuleId()`.
- Tabs and DialogSelector ship as separate bundles in `mb.admin.kit` (`src/tabs`, `src/dialog-selector` → `dist/*.bundle.js`); no shared `initAll` — each `TabsRenderer` / `DialogSelectorRenderer` initializes its own instance.
- `Tabs::extension()` removed; `AssetManager::forForm()` no longer loads `mb.ui.tabs` separately.
- `Resource` is now a minimal core class for identity, menu, and pages.
- `CrudResource` is now a DSL-only class without persistence logic.
- All ORM-backed examples and fixtures migrated to `DataManagerResource`.
- Internal services (`GridDataLoader`, `IndexPage`, `FormPage`, `DetailPage`, `ExportAction`) updated to use narrow contracts.
- `HasDataManagerPersistence::findItem` now uses explicit `=` operator for primary key filter.
- `ExportAction` now uses explicit `@` operator for selected IDs filter.
- `OptionsPage::fields()` is now `public` in documentation and examples.
- `Field` base class moved to concern-based composition (`Field\Concerns\*`) and no longer acts as a monolith.
- `AbstractLayoutComponent` delegates child rendering to `ChildrenRenderer` and no longer renders field rows directly.
- `FormPage` and `OptionsPage` now render form rows via `FieldRowRenderer`.
- `Tabs` rendering is delegated to `TabsRenderer`/`TabBodyRenderer`; duplicate field-row rendering and debug `console.log` removed.
- `AbstractWidget` is now a leaf dashboard component (no inheritance from layout containers).
- Dashboard rendering and extension collection moved from `DashboardPage` into `Widget\Dashboard\DashboardRenderer`.
- Chart widgets no longer load external Chart.js CDN from PHP; chart init is local-extension driven.
- `BulkAction::delete()` now returns `MassDeleteAction` directly; the index bulk handler no longer substitutes delete actions at runtime.
- `MassDeleteAction` now shares the delete factory UI defaults (`danger`, remove icon, danger group, confirmation, sort order).

### Fixed
- Grid inline-edit metadata now respects field readonly state: `readonly()` fields no longer publish editable config into `main.ui.grid` columns.
- Relation and entity selector fields are now explicitly non-inline-editable in grid metadata to avoid unstable runtime editors and enforce form/sidepanel editing flows.
- `Select` grid column metadata now exports editable `items` only when inline editing is actually enabled for the column.
- `BelongsTo` (and `BelongsToMany`) grid metadata now explicitly disables inline editing, aligning relation-like selects with stable sidepanel/form editing flows and fixing failing tests/CI checks.
- Localized hardcoded user-facing messages in actions, bulk results, relation components, selectors, validation rules, import/export handlers, and CRUD page notifications by moving them to `Loc` message files (`lang/ru` and `lang/en` for the affected classes).
- Updated key documentation files to Russian and synchronized quick-start/install/architecture/grid/import-export/backward-compatibility guides with the current package behavior.
- Options page: fields on inactive Bitrix tabs (e.g. `BelongsTo`) are included in AJAX save by temporarily enabling disabled inputs before `FormData` is built.
- Options page: `Password` and other fields with `preserveStoredValueWhenEmpty()` no longer clear stored values when the posted value is empty.
- Options page: remembered tab id is applied before render so the correct tab is active after reload.
- CSRF: added missing `sessid` to native Bitrix grid bulk actions.
- ORM: fixed potential issues with ambiguous primary key filters by using explicit Bitrix ORM operators.
- `IndexPage`: restored base `grouping()` hook so `IndexPageDefinition` and `RowAssembler` receive resource `indexGrouping()` (group rows were missing when only collapsible UI was enabled).
- Group labels: `GroupLabelRenderer` now resolves section titles from `__GROUP_DATA` (`NAME`/`TITLE` fallback) and renders `ungroupedLabel()` for the ungrouped bucket; collapsible shift column prefers `NAME` when grouping has no explicit `labelColumn`.
- Bulk action dropdowns now render their label as a non-executable placeholder item, so Bitrix `Types::DROPDOWN` shows the dropdown label/placeholder instead of the first child action.
- Invisible direct bulk actions and invisible dropdown child actions are filtered out of `ACTION_PANEL`; dropdowns with no visible executable children are skipped.
- For-all bulk mode now uses the explicit `action_all_rows_<GRID_ID>` checkbox flag and ignores selected IDs in that mode.
- `QueryGuard` now blocks unsafe for-all operations without `allowRunByFilter()`, empty-filter/full-table operations without `allowRunWithoutFilter()`, and operations above `maxBulkRows()`.
- Custom `BulkAction` handlers now honor action-level `canRun()` before invoking the handler.

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
- Grid inline-edit metadata now respects field readonly state: `readonly()` fields no longer publish editable config into `main.ui.grid` columns.
- Relation and entity selector fields are now explicitly non-inline-editable in grid metadata to avoid unstable runtime editors and enforce form/sidepanel editing flows.
- `Select` grid column metadata now exports editable `items` only when inline editing is actually enabled for the column.
- `BelongsTo` (and `BelongsToMany`) grid metadata now explicitly disables inline editing, aligning relation-like selects with stable sidepanel/form editing flows and fixing failing tests/CI checks.
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
