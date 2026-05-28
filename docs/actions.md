# Actions

## Что это

Action — пользовательское действие в админке.

- `RowAction` — действие над одной строкой в grid.
- `BulkAction` — действие над несколькими выбранными строками.
- `BulkActionDropdown` — UI-контейнер для группировки `BulkAction` в dropdown action-panel.

## Когда использовать RowAction

- открыть detail/view;
- открыть edit (в т.ч. через SidePanel);
- удалить одну запись;
- запустить custom operation для конкретной записи.

## RowAction vs BulkAction

| Тип | Где отображается | Сколько записей обрабатывает | Где описывать |
|---|---|---:|---|
| RowAction | В меню конкретной строки | 1 | `rowActions()` |
| BulkAction | В нижней action panel grid | N | `bulkActions()` |

## Базовый пример row actions

```php
use MB\Bitrix\AdminKit\Action\RowAction;

public function rowActions(): iterable
{
    return [
        RowAction::view(),
        RowAction::edit(),
        RowAction::delete(),
    ];
}
```

## Доступные методы RowAction

`RowAction` в текущей версии минималистичный: основной пользовательский API — статические конструкторы.

| Метод | Что делает | Когда использовать |
|---|---|---|
| `RowAction::view(?string $url = null)` | Добавляет action просмотра | Открытие detail/view |
| `RowAction::edit(?string $url = null)` | Добавляет action редактирования | Переход на form/edit |
| `RowAction::delete()` | Добавляет delete с confirm | Опасные операции по одной записи |

`getId()/getLabel()/getUrl()/toArray()` — runtime/internal API для рендера и адаптеров.

## Confirm / dangerous actions

`RowAction::delete()` уже включает confirm-диалог и тип `delete`.

## Permissions

Видимость и выполнение контролируются на уровне:

- страницы/ресурса (`canView`, `canUpdate`, `canDelete`);
- action handlers;
- `PermissionContext` для опасных действий.

Проверки action не заменяют per-item проверки прав ресурса.

## Async actions

Асинхронные JSON-endpoint действия описаны отдельно:

- [Guide: Async actions](user/guides/async-actions.md)
- [Reference: Actions](user/reference/actions.md)

## Практические сценарии

- ссылка на edit/detail;
- delete с confirm;
- скрытие/запрет исполнения через permission policy ресурса;
- открытие edit во SidePanel.

## Связанные разделы

- [Grid](grid.md)
- [Bulk actions](bulk-actions.md)
- [Permissions](user/guides/permissions.md)
- [UI integration](user/guides/ui-integration.md)
