# Транзакции

CRUD-ресурсы по умолчанию используют транзакции. Переопределите `CrudResource::useTransactions(): bool`, чтобы отключить их для ресурса.

При включённых транзакциях create, update, delete и массовое удаление выполняются через `TransactionManager`, который использует методы соединения Bitrix D7: `Application::getConnection()->startTransaction()`, `commitTransaction()` и `rollbackTransaction()`.

Если lifecycle-хук выбрасывает исключение Admin Kit, операция останавливается и транзакция откатывается.
