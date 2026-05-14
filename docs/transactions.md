# Transactions

CRUD resources use transactions by default. Override `CrudResource::useTransactions(): bool` to disable them for a resource.

When transactions are enabled, create, update, delete, and mass delete operations run through `TransactionManager`, which uses Bitrix D7 connection methods: `Application::getConnection()->startTransaction()`, `commitTransaction()`, and `rollbackTransaction()`.

If a lifecycle hook throws an Admin Kit exception, the operation is stopped and the transaction is rolled back.
