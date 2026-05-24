# Add RowAction

## Задача

Добавить действия для каждой строки Grid.

## Когда использовать

Для перехода в просмотр/редактирование, удаления, запуска кастомного action.

## Решение

Определите `rowActions()` в ресурсе. Базовые методы: `RowAction::view()`, `RowAction::edit()`, `RowAction::delete()`. Для кастомных действий используйте `new RowAction('id', 'Label')`.

## Полный пример

```php
use MB\Bitrix\AdminKit\Action\RowAction;

public function rowActions(): iterable
{
    return [
        RowAction::view(),
        RowAction::edit(),
        RowAction::delete(),
        new RowAction('audit', 'Audit log'),
    ];
}
```

## Как это работает

`view/edit` по умолчанию открываются в SidePanel. `delete` включает confirm и добавляет `sessid` в URL.

## Что важно учесть

- Для destructive действий оставляйте confirm.
- Проверки прав выполняйте на уровне ресурса/обработчика действия.
- SidePanel должен быть нативный (`BX.SidePanel.Instance.open`).

## Связанные разделы

- [Actions](../../actions.md)
- [Reference: Actions](../reference/actions.md)
- [Use SidePanel](sidepanel.md)
