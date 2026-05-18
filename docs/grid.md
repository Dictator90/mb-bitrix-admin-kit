# Grid

The grid layer is split into small services so Bitrix UI concerns stay separate from ORM query execution.

## Responsibilities

- `GridQueryBuilder` is the only source of ORM parameters. It builds `select`, `filter`, `order`, `runtime`, `limit`, and `offset` from Resource fields, filters, `GridContext`, default Resource query configuration, runtime fields, and index query hooks. It also validates and sanitizes sorting parameters against allowed sortable columns to prevent unsafe queries.
- `GridDataLoader` creates the `GridContext`, calls `GridQueryBuilder`, applies `QueryGuard`, resolves total counts and count cache, calls `DataManager::getList($params)`, feeds the result into `Grid::setRawRows()`, and returns `QueryPerformanceContext`.
- `Grid` stores UI state: grid id, filter id, fields, filters, row actions, bulk actions, rows, total count, and `PageNavigation`. It does not build ORM params.
- `BitrixGridAdapter` builds `bitrix:main.ui.grid` component params such as columns, rows, sorting UI state, navigation, total count, inline-edit flags, AJAX flags, and action-panel integration.
- `BitrixFilterAdapter` builds `bitrix:main.ui.filter` component params and returns `null` when the grid has no filters.
- `BitrixGridActionPanelAdapter` builds action-panel groups, sorts groups by `groupSort`, sorts actions within groups, filters invisible actions, handles icons and CSS classes, integrates the inline edit button, the "Select all records" checkbox, dropdown bulk actions, and standard Bitrix panel constants. Dropdowns insert a placeholder as the first item because Bitrix `Types::DROPDOWN` shows the selected/first item as its visible label. It generates JavaScript callbacks that submit selected IDs and the explicit `action_all_rows_<GRID_ID>` for-all flag through native grid AJAX reload.
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

## Selectable fields and custom columns

By default, `GridQueryBuilder` selects all non-computed field columns. You can fine-tune this behavior:

- `Field::selectable(false)` — prevents the field from being added to the ORM `select` list. Useful for virtual or display-only fields that should not trigger DB selection.
- `Field::selectColumns(['COL_A', 'COL_B'])` — forces the selection of specific DB columns for this field instead of its main column.

## Sort validation

`GridQueryBuilder` validates the `order` parameter from the UI or request. It only allows sorting by columns that are explicitly marked as sortable in the field configuration (via `Field::sortable(true)` or custom `getGridColumnConfig()['sort']`). Sort directions are normalized to `ASC` or `DESC`.

## Grouped index rows

AdminKit supports grouped index grids through the existing `IndexPage` flow. The resource still describes **only the main item table** in `indexFields()`, while grouping is declared separately with `Grid\Grouping\IndexGrouping`.

```php
use MB\Bitrix\AdminKit\Field\HasMany;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;

public function indexFields(): iterable
{
    return [
        Text::make('Наименование', 'NAME')->asEditLink(),

        HasMany::make('Сайты', 'SITES')
            ->table(SiteCookieTable::class)
            ->foreignKey('COOKIE_ID')
            ->localKey('ID')
            ->value('SITE_ID')
            ->displayUsing(fn (array $sites): string => implode(', ', $sites)),
    ];
}

public function indexGrouping(): ?IndexGrouping
{
    return IndexGrouping::make()
        ->resource(CookieGroupResource::class)
        ->foreignKey('GROUP_ID')
        ->ownerKey('ID')
        ->parentKey('PARENT_ID')
        ->label('NAME')
        ->labelColumn('NAME')
        ->order(['SORT' => 'ASC', 'ID' => 'ASC'])
        ->expand(false)
        ->fullWidth(true)
        ->ungroupedLabel('Без группы');
}
```

`IndexGrouping::fullWidth(true)` renders group headers as Bitrix `custom` rows (`main-grid-row-custom`) spanning the full grid width. Nested group headers receive a left indent via `adminkit-grid-group-label--depth-*` classes.

Use `->align('left'|'center'|'right')` (default `left`) — the same key as `COLUMNS[].align` in `main.ui.grid` (`main-grid-cell-left|center|right`). For `fullWidth()` Bitrix still renders `main-grid-cell-center` on the custom `<td>`; AdminKit sets `data-align` on the row and overrides via CSS.

`IndexPage::grouping()` proxies `Resource::indexGrouping()` by default. A custom index page may override `grouping()` to change grouping for that page or return `null` to disable resource-level grouping.

When grouping is enabled, AdminKit passes `ENABLE_COLLAPSIBLE_ROWS` to `main.ui.grid`, sets `shift => true` on the grouping label column (required for the Bitrix +/- control), marks group rows as preloaded (`data-child-loaded`) so Bitrix uses `showChildRows()` instead of `GRID_GET_CHILD_ROWS`, hides descendants while any ancestor group is collapsed (Bitrix renders all rows in HTML on first paint), and emits synthetic row IDs:

- `group:{id}` for group rows;
- `item:{id}` for item rows;
- `group:__ungrouped` for the optional ungrouped bucket.

Bulk actions and inline editing ignore `group:*` IDs and normalize `item:*` back to the original item ID before calling item-resource CRUD methods.

Group labels are rendered as edit links for the grouped resource. If the grouped resource enables `editInSidePanel()`, the link opens through Bitrix SidePanel; otherwise it is a normal edit URL. Item labels can be turned into edit links with `Field::asEditLink()` or its alias `Field::linkToEdit()`.

## HasMany / HasOne relation fields

`HasMany` and `HasOne` are index fields for related labels or values. They do not add JOINs to the main grid query, and therefore do not multiply base rows. `GridQueryBuilder` excludes relation field columns from the ORM `select` but ensures each relation `localKey()` is selected. After item rows are fetched and `afterIndexRows()` runs, AdminKit performs one relation query per relation field and writes the loaded value into the row before rendering.

```php
HasOne::make('Группа', 'GROUP_NAME')
    ->table(CookieGroupTable::class)
    ->foreignKey('ID')
    ->localKey('GROUP_ID')
    ->value('NAME')
    ->default('—');
```

`HasMany` returns an array of values; `HasOne` returns the first ordered value or its default/null. Both support `filter()`, `order()`, and `value()` as a column name or closure.

Current limitations: pagination is still applied to item rows before grouping, groups without matching items are not loaded by the grouping flow, lazy loading of children is not implemented, and the index import flow remains disabled.
