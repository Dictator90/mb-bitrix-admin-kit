# Lifecycle-хуки

CRUD-ресурсы могут переопределить lifecycle-хуки v0.3.0:

- `beforeValidate(FormData $data, DbOperationContext $context)`
- `afterValidate(FormData $data, DbOperationContext $context)`
- `beforeCreate()` / `afterCreate()`
- `beforeUpdate()` / `afterUpdate()`
- `beforeDelete()` / `afterDelete()`
- `beforeMassDelete()` / `afterMassDelete()`

Устаревшие хуки `beforeCreating`, `afterCreated`, `beforeUpdating`, `afterUpdated`, `beforeDeleting`, `afterDeleted`, `beforeMassDeleting` и `afterMassDeleted` по-прежнему вызываются новыми хуками для обратной совместимости.
