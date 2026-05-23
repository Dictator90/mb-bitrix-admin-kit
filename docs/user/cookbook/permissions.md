# Как проверить права

```php
use MB\Bitrix\AdminKit\Security\PermissionContext;

public function canUpdate(array|object|null $item = null, ?PermissionContext $context = null): bool
{
    return $context?->isAdmin() ?? false;
}
```

Подробно: [Guide: Permissions](../guides/permissions.md)
