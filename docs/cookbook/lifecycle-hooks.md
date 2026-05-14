# Как добавить beforeCreate/afterUpdate

```php
protected function beforeCreate(array $data): array
{
    $data['CREATED_BY'] = $GLOBALS['USER']?->GetID();

    return $data;
}

protected function afterUpdate(int|string $id, array $data): void
{
    // Очистить cache или отправить событие.
}
```

Хуки не должны выводить HTML или делать debug echo.
