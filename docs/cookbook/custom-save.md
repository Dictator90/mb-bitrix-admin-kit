# Как сделать кастомный save

Переопределите методы CRUD Resource, но оставьте результат совместимым с `DbResult` и не обходите `Form\DataPipeline` без необходимости:

```php
protected function beforeCreate(array $data): array
{
    $data['XML_ID'] = md5($data['NAME']);

    return $data;
}
```

Для низкоуровневых ORM-ошибок используйте `CrudPersister`.
