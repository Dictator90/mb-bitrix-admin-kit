# Grid

The grid layer renders index fields, applies filters, orders rows, handles pagination, and delegates ORM parameter building to `GridQueryBuilder`.

## GridContext

`GridContext` is part of the stable public API. It represents request/grid state such as filter values, sorting, pagination, selected rows, and Resource context. Minor and patch releases must not break its format.

## Query assembly order

`GridQueryBuilder` combines field select, Resource defaults, UI filter input, Resource query hooks, runtime fields, sort, and pagination in a deterministic order. Runtime field objects are passed through to `DataManager::getList()` parameters.

## Computed columns

Computed columns are calculated after ORM rows are loaded and are not selected automatically from the database.
