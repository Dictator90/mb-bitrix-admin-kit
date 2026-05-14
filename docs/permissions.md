# Permissions

`PermissionContext` describes access checks with `userId`, `moduleId`, `resource`, `operation`, and `item`.

`CrudResource` exposes `canView()`, `canCreate()`, `canUpdate()`, and `canDelete()` methods accepting a `PermissionContext`. Pages check permissions before rendering create actions, opening forms, saving data, deleting rows, and mass deleting rows.
