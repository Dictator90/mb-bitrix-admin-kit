# CrudPersister и ошибки ORM

`MB\Bitrix\AdminKit\Database\CrudPersister` — единая точка низкоуровневого сохранения для CRUD-операций.

- `create()` вызывает `DataManager::add()`.
- `update()` вызывает `DataManager::update()`.
- `delete()` вызывает `DataManager::delete()`.

Ошибки Bitrix ORM `Result` преобразуются в `DbResult::error()` со всеми доступными сообщениями. Страницы форм показывают эти сообщения пользователю и никогда не считают неуспешный ORM-результат успешным сохранением.
