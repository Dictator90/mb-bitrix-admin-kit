# Как проверить права

```php
public function canDelete(array|object|null $item = null, ?PermissionContext $context = null): bool
{
    return $context?->isAdmin() === true;
}
```

Для опасных actions используйте `PermissionContext`; bulk operations проверяют права по каждой записи.
