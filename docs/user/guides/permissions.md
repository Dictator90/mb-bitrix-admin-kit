# Права доступа и PermissionContext

## Когда использовать

Когда нужно ограничить видимость страниц и опасные операции.

## Минимальный пример

```php
use MB\Bitrix\AdminKit\Security\PermissionContext;

public function canDelete(array|object|null $item = null, ?PermissionContext $context = null): bool
{
    return $context?->isAdmin() ?? false;
}
```

Матрица проверок:

| Зона | Проверки |
|------|----------|
| `IndexPage` | `canView`, `canUpdate`/`canDelete` для inline/bulk |
| `FormPage` | `canView`, `canCreate` (create), `canUpdate` (edit) |
| `DetailPage` | `canView` |
| Export | `canView` + action `canRun()` |
| Bulk | `canRun()` + поэлементно `canUpdate`/`canDelete` |
| `OptionsPage` | `canView` + `sessid` |

## Ограничения

- Не заменяйте per-record проверки глобальной проверкой только на уровне списка.
- Skipped records в bulk не должны прерывать всю операцию.

## См. также

- [Bulk actions](bulk-actions.md)
- [Reference: Actions](../reference/actions.md)
