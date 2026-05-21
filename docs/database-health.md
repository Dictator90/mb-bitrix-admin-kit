# Диагностика БД и схемы

v0.6.0 добавляет read-only диагностику базы данных для CRUD-ресурсов. Диагностика никогда не создаёт таблицы,
не добавляет колонки и индексы и не запускает миграции из админ-UI.

## Объявление ожидаемой схемы

Ресурсы, которым нужна диагностика, могут реализовать `SchemaAwareResource`:

```php
use MB\Bitrix\AdminKit\Database\Schema\TableSchema;
use MB\Bitrix\AdminKit\Resource\SchemaAwareResource;

final class ProductResource extends DataManagerResource implements SchemaAwareResource
{
    public function expectedTableSchema(): TableSchema
    {
        return TableSchema::make('vendor_product')
            ->column('ID', 'int', required: true)
            ->column('NAME', 'string', required: true)
            ->column('ACTIVE', 'string', required: true)
            ->index('PRIMARY', ['ID'])
            ->index('IX_ACTIVE', ['ACTIVE']);
    }
}
```

`CrudResource::databaseTableName()` определяет таблицу из `DataManager::getTableName()`, если ORM-класс её предоставляет.

## Проверка таблиц

`DatabaseSchemaInspector` через соединение Bitrix проверяет:

- наличие таблицы;
- колонки;
- индексы.

`TableHealthCheck` сравнивает объявленную `TableSchema` с живой БД и сообщает:

- отсутствующую таблицу;
- отсутствующие обязательные колонки;
- отсутствующие индексы;
- безопасные базовые несовпадения типов, когда тип можно определить.

## Опциональная страница health

`MB\Bitrix\AdminKit\Page\System\DatabaseHealthPage` принимает iterable ресурсов и рендерит диагностическую таблицу с id ресурса, классом DataManager, именем таблицы, отсутствующими колонками, отсутствующими индексами и статусом.

Страница намеренно опциональна. Регистрируйте её только в разделах tools или admin, где диагностика уместна для привилегированных пользователей.
