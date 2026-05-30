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

## ToolbarAction

Кнопка тулбара index-страницы. Возвращается из `Resource::toolbarActions()`.
Рендерится в `Bitrix\UI\Buttons\Button` (или `Split\Button`) через `ToolbarRenderer`.

Может быть простой кнопкой/ссылкой, кнопкой с выпадающим меню (`items()`) или split-кнопкой (`split()`).

```php
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Toolbar\ButtonLocation;
use MB\Bitrix\AdminKit\Manager\ToolbarAction;
use MB\Bitrix\AdminKit\Support\UrlGenerator;

public function toolbarActions(): iterable
{
    $urls = UrlGenerator::forCurrentRequest(\Bitrix\Main\Context::getCurrent()->getRequest());

    return [
        ToolbarAction::make('Создать')
            ->color(Color::SUCCESS)
            ->icon(Icon::ADD)
            ->location(ButtonLocation::AFTER_FILTER)
            ->canSee($this->hasModuleWriteAccess())
            ->split()                                   // основное действие + стрелка с меню
            ->url($urls->resourceUrl(static::getId(), ['action' => 'add']))
            ->sidePanel($this->sidePanelWidth())        // открыть в слайдере
            ->items([
                ToolbarAction::make('Создать тип')
                    ->url($urls->resourceUrl('cookie_type', ['action' => 'add']))
                    ->sidePanel($this->sidePanelWidth()),
            ]),
    ];
}
```

### Методы ToolbarAction

| Метод | Возвращает | Что делает | Когда использовать |
|---|---|---|---|
| `make($label, $id = '')` | `self` | Создает кнопку (id генерируется из label, если пуст) | Базовая инициализация |
| `url($url)` | `self` | Ссылка (переход по клику) | Обычная кнопка-ссылка |
| `onclick($js)` | `self` | Произвольный JS-обработчик клика вместо перехода | Кастомный клик |
| `sidePanel($width = 1100, $gridId = null)` | `self` | Открывать `url` в слайдере (`BX.SidePanel`) с перезагрузкой грида при закрытии | Создание/редактирование без перезагрузки страницы |
| `color($color)` | `self` | Цвет (`Bitrix\UI\Buttons\Color`, напр. `SUCCESS`) | Стилизация |
| `icon($icon)` | `self` | Иконка (`Bitrix\UI\Buttons\Icon`, напр. `ADD`) | Стилизация |
| `items(iterable)` / `addItem(ToolbarAction)` | `self` | Вложенные пункты — кнопка становится дропдауном | Меню из ссылок/действий |
| `split($flag = true)` | `self` | Рендерить как split-кнопку (главная + стрелка с меню) | Основное действие + альтернативы |
| `location($location)` | `self` | Позиция в тулбаре (`Bitrix\UI\Toolbar\ButtonLocation`) | Размещение кнопки |
| `counter($value)` | `self` | Счётчик-бейдж на кнопке | Показать число (новых/выбранных и т.п.) |
| `size($size)` | `self` | Размер (`Bitrix\UI\Buttons\Size`, напр. `SMALL`) | Компактная/крупная кнопка |
| `disabled($flag = true)` | `self` | Отключённое состояние (`State::DISABLED`) | Недоступное действие |
| `round($flag = true)` | `self` | Круглая кнопка | Иконочные round-кнопки |
| `collapsedIcon($icon)` | `self` | Иконка для адаптивного «свёрнутого» состояния | Кнопки без текста при нехватке места |
| `class($class)` | `self` | CSS-класс | Тонкая стилизация |
| `canSee(bool/callable/ConditionTree)` | `self` | Видимость кнопки | Скрыть без прав |

`location()` принимает значения `ButtonLocation`: `AFTER_TITLE` (по умолчанию), `RIGHT`, `AFTER_FILTER`, `AFTER_NAVIGATION`.

Пункты меню (`items()`) — это те же `ToolbarAction`: у каждого работает `url()`/`onclick()`/`sidePanel()`.

> Экспорт **не** добавляется через `toolbarActions()` — он управляется единым флагом `exportEnabled()` (см. [Resources](resources.md) и [Import/Export](../guides/import-export.md)).

## Common action API

| Метод | Класс | Возвращает | Что делает | Когда использовать |
|---|---|---|---|---|
| `make($id, $label)` | `BulkAction`, `BulkActionDropdown` | `static` | Создает action/container | Базовая инициализация |
| `label($label)` | `BulkAction`, `BulkActionDropdown` | `static` | Меняет текст | Локализация/UI |
| `group($group, $label = null, $sort = null)` | `BulkAction`, `BulkActionDropdown` | `static` | Группирует в action panel | Логическая группировка |
| `sort($sort)` | `BulkAction`, `BulkActionDropdown` | `static` | Порядок в группе | Контроль UX-порядка |
| `title($title)` | `BulkAction`, `BulkActionDropdown` | `static` | `TITLE` для Bitrix control | Tooltip/подсказка |
| `icon($icon)` / `class($class)` | `BulkAction`, `BulkActionDropdown` | `static` | CSS-классы action panel | Стилизация кнопок |
| `color($cssClass)` | `BulkAction` | `static` | Цвет кнопки (CSS-класс `Bitrix\UI\Buttons\Color`, напр. `ui-btn-success`) | Семантический цвет |
| `primary()` / `success()` / `secondary()` / `light()` / `link()` | `BulkAction` | `static` | Шорткаты к `color()` с готовым классом | Быстрая стилизация |
| `confirm($text = null)` | `BulkAction` | `static` | Включает confirm | Опасные операции |
| `danger(true)` | `BulkAction` | `static` | Добавляет danger-style (`ui-btn-danger`) | Delete/critical actions |
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
