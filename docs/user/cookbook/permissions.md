# Permissions

## Задача

Ограничить доступ к ресурсу и действиям по ролям/контексту.

## Когда использовать

Всегда, когда ресурс содержит изменение или удаление данных.

## Решение

Переопределите `canView/canCreate/canUpdate/canDelete` в ресурсе. Для action-уровня применяйте `canSee()/canRun()` и проверяйте доступ в обработчиках.

## Полный пример

```php
use MB\Bitrix\AdminKit\Action\BulkAction;

public function canDelete(array $row = []): bool
{
    return $this->user->isAdmin();
}

public function bulkActions(): iterable
{
    return [
        BulkAction::delete()->canRun(fn () => $this->user->isAdmin()),
    ];
}
```

## Как это работает

Grid и action panel учитывают видимость/доступность, но финальная проверка должна быть в серверном обработчике каждой операции.

## Что важно учесть

- Для bulk-операций права проверяются по каждой записи; skip не должен падать в fatal.
- Опасные действия защищайте CSRF и explicit confirm.

## Связанные разделы

- [Guides: Permissions](../guides/permissions.md)
- [Reference: Actions](../reference/actions.md)
- [Reference: Resources](../reference/resources.md)
