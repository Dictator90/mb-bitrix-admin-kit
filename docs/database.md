# Database integration

AdminKit is designed around Bitrix D7 ORM `DataManager` classes. A `CrudResource` exposes its ORM class with `dataManagerClass()` and the grid/form/persister layers build parameter arrays that are passed to documented ORM APIs.

## Result objects

- `DbOperationContext` carries operation metadata such as operation type, id, data, Resource, and permission context.
- `DbResult` wraps successful ids or low-level ORM errors.
- `BulkOperationContext` carries selected ids, filter mode, request data, and Resource metadata for bulk actions.
- `BulkResult` reports processed, skipped, failed, and error rows without aborting an entire batch on a single skipped record.

## Runtime fields

Pass Bitrix runtime field objects through Resource hooks. AdminKit should not create business-specific joins in the grid layer.

## Read-only diagnostics

Database health helpers inspect expected and actual schema. Admin pages must not create, drop, or alter tables on ordinary page open.
