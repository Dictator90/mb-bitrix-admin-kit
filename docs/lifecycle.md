# Lifecycle

Lifecycle hooks allow Resources to customize create, update, delete, import, and bulk operations without replacing the persistence pipeline.

## Recommended use

- Prepare values before save in Resource/Field hooks.
- Validate business rules before persistence.
- React after successful persistence.
- Keep permission checks in `PermissionContext` and Resource methods.

Hooks should be additive and backward-compatible. Removing or renaming public/protected lifecycle methods requires deprecation first.
