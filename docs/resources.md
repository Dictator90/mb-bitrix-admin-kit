# Resources

A Resource is the public description of an admin section: title, identifier, fields, filters, actions, permissions, menu metadata, and page rendering settings.

## Stable public contract

Resource-level APIs should keep accepting and returning simple values: `array`, `iterable`, `string`, `bool`, `int`, `mixed`, `callable`, or `Closure` where callbacks are expected. Public module code should not have to depend on `MB\Support\Collection`, global helpers, or internal adapter classes.

## Typical responsibilities

- Return field lists from `indexFields()`, `formFields()`, and `detailFields()`.
- Return filters from `filters()`.
- Return row and bulk actions from `rowActions()` and `bulkActions()`.
- Define permissions with `canView()`, `canCreate()`, `canUpdate()`, `canDelete()`, and action-level checks.
- Configure SidePanel and admin menu metadata.

## Extension guidance

Prefer extending the existing Resource and Field classes instead of introducing a parallel abstraction. Keep business-specific Bitrix ORM joins in the Resource query hooks, not in generic grid code.
