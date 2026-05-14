# CrudPersister and ORM errors

`MB\Bitrix\AdminKit\Database\CrudPersister` is the single low-level persistence entry point for CRUD operations.

- `create()` calls `DataManager::add()`.
- `update()` calls `DataManager::update()`.
- `delete()` calls `DataManager::delete()`.

Bitrix ORM `Result` errors are converted to `DbResult::error()` with all available messages. Form pages render these messages as user-visible errors and never treat failed ORM results as successful saves.
