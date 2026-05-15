# Grid

The grid layer is split into small services so Bitrix UI concerns stay separate from ORM query execution.

## Responsibilities

- `GridQueryBuilder` is the only source of ORM parameters. It builds `select`, `filter`, `order`, `runtime`, `limit`, and `offset` from Resource fields, filters, `GridContext`, default Resource query configuration, runtime fields, and index query hooks.
- `GridDataLoader` creates the `GridContext`, calls `GridQueryBuilder`, applies `QueryGuard`, resolves total counts and count cache, calls `DataManager::getList($params)`, feeds the result into `Grid::setRawRows()`, and returns `QueryPerformanceContext`.
- `Grid` stores UI state: grid id, filter id, fields, filters, row actions, bulk actions, rows, total count, and `PageNavigation`. It does not build ORM params.
- `BitrixGridAdapter` builds `bitrix:main.ui.grid` component params such as columns, rows, sorting UI state, navigation, total count, inline-edit flags, AJAX flags, and action-panel integration.
- `BitrixFilterAdapter` builds `bitrix:main.ui.filter` component params and returns `null` when the grid has no filters.
- `BitrixGridActionPanelAdapter` builds action-panel groups, the inline edit button, bulk-action buttons, confirm messages, danger classes, and JavaScript callbacks that submit selected IDs through native grid AJAX reload.
- `ToolbarRenderer` integrates `Toolbar::addFilter()`, the create button, create SidePanel behavior, SidePanel width, direct create navigation, and final `bitrix:ui.toolbar` rendering.

## GridContext

`GridContext` represents request/grid state such as filter values, sorting, pagination, selected rows, and Resource context. Data loading services pass it to Resource query hooks and row mapping hooks.

## Query assembly order

`GridQueryBuilder` combines field select, Resource defaults, UI filter input, Resource query hooks, runtime fields, sort, and pagination in a deterministic order. Runtime field objects are passed through to `DataManager::getList()` parameters.

The builder accounts for:

- `indexFields()` and configured filters;
- `defaultSort()`, `defaultFilter()`, and `defaultSelect()`;
- `runtimeFields()`;
- `indexSelect()`, `indexFilter()`, `indexOrder()`, and `indexRuntime()`;
- `beforeIndexQueryParams()` and `modifyIndexParams()`.

## IndexPage integration

`Page\IndexPage` supplies fields, filters, row/bulk actions, and query hooks to `GridDataLoader`, `GridQueryBuilder`, and `RowAssembler`. It does not build ORM params itself. CSV export on the index is handled by `ExportAction` (not in the grid layer); import UI on index is temporarily disabled.

## Data loading

Index pages should delegate reads to `GridDataLoader` instead of calling ORM methods directly. The loader keeps performance behavior centralized:

1. create `GridContext` from the Resource, grid ids, pagination, and request;
2. build ORM params through `GridQueryBuilder`;
3. guard limits through `QueryGuard`;
4. run total count only when `useTotalCount($context)` allows it;
5. cache counts according to `countCacheTtl($context)`;
6. call `DataManager::getList($params)`;
7. pass the ORM result to `Grid::setRawRows()`;
8. return `QueryPerformanceContext` for diagnostics.

## Bitrix UI adapters

Use the adapters when rendering Bitrix components:

```php
$grid = new Grid($resource->getGridId(), $fields, $filters, $rowActions);
$performance = (new GridDataLoader())->load($resource, $grid, $request);

Toolbar::addFilter((new BitrixFilterAdapter())->componentParams($grid));
$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', (new BitrixGridAdapter())->componentParams($grid));
```

`Grid::getGridComponentParams()` and `Grid::getFilterComponentParams()` remain convenience delegators to these adapters.

## Computed columns

Computed columns are calculated after ORM rows are loaded and are not selected automatically from the database.
