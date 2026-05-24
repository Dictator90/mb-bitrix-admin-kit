# Как добавить RowAction

## Задача

Добавить действие для одной строки Grid (просмотр, редактирование, удаление, custom).

## Решение

Верните список `RowAction` через `rowActions()`.

## Полный пример

```php
use MB\Bitrix\AdminKit\Action\RowAction;

public function rowActions(): iterable
{
    return [
        RowAction::edit(),
        RowAction::delete(),
    ];
}
```

## Что важно учесть

- `RowAction` применяется к одной записи, для массового сценария используйте `BulkAction`.
- Для опасных действий добавляйте confirm и permission checks на уровне ресурса.

## Связанные разделы

- [Actions](../../actions.md)
- [Reference: Actions](../reference/actions.md)
- [Guide: Permissions](../guides/permissions.md)
