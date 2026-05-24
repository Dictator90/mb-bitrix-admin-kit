# Reference: Actions

## RowAction

Класс для действий по одной строке.
Публичный fluent API в текущей версии ограничен статическими конструкторами:

- `RowAction::view(?string $url = null)`
- `RowAction::edit(?string $url = null)`
- `RowAction::delete()`

## BulkAction

Исполняемое массовое действие.
Поддерживает visibility/run checks, confirm, group/sort, custom handler, run-by-filter режим.

## BulkActionDropdown

UI-контейнер для группировки bulk actions в dropdown.

Важно: `BulkActionDropdown` не исполняет операцию сам, исполняются только вложенные `BulkAction`.

## Common action API

| Метод | Класс | Возвращает | Что делает | Когда использовать |
|---|---|---|---|---|
| `make($id, $label)` | `BulkAction`, `BulkActionDropdown` | `static` | Создает action/container | Базовая инициализация |
| `label($label)` | `BulkAction`, `BulkActionDropdown` | `static` | Меняет текст | Локализация/UI |
| `group($group, $label = null, $sort = null)` | `BulkAction`, `BulkActionDropdown` | `static` | Группирует в action panel | Логическая группировка |
| `sort($sort)` | `BulkAction`, `BulkActionDropdown` | `static` | Порядок в группе | Контроль UX-порядка |
| `title($title)` | `BulkAction`, `BulkActionDropdown` | `static` | `TITLE` для Bitrix control | Tooltip/подсказка |
| `icon($icon)` / `class($class)` | `BulkAction`, `BulkActionDropdown` | `static` | CSS-классы action panel | Стилизация кнопок |
| `confirm($text = null)` | `BulkAction` | `static` | Включает confirm | Опасные операции |
| `danger(true)` | `BulkAction` | `static` | Добавляет danger-style | Delete/critical actions |
| `canSee(...)` | `BulkAction` | `static` | Видимость action | Скрыть action без прав |
| `canRun(...)` | `BulkAction` | `static` | Разрешение запуска | Runtime-ограничения |
| `handle(Closure)` / `executeUsing(Closure)` | `BulkAction` | `static` | Устанавливает handler | Кастомная bulk-логика |
| `allowRunByFilter(true)` | `BulkAction` | `static` | Разрешает all-by-filter режим | Массовые операции по фильтру |
| `allowRunWithoutFilter(true)` | `BulkAction` | `static` | Разрешает запуск без фильтра | Только для явно безопасных кейсов |
| `item(BulkAction)` / `items(iterable)` | `BulkActionDropdown` | `static` | Добавляет дочерние actions | Dropdown-группы |
| `placeholder($text)` | `BulkActionDropdown` | `static` | Placeholder для dropdown | Label-подсказка |

## Extension points vs internal/runtime

- **Пользовательский API:** fluent методы выше.
- **Extension points:** callback `handle()`, условия `canSee/canRun`, grouping/sorting.
- **Runtime/internal:** `getId()/getLabel()/getPanelType()/toArray()/isVisible()` и другие getters для адаптеров/handlers.

## Связанные разделы

- [Actions overview](../../actions.md)
- [Bulk actions overview](../../bulk-actions.md)
- [Guide: Async actions](../guides/async-actions.md)
