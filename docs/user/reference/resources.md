# Reference: Resources

## Базовые классы

- `MB\Bitrix\AdminKit\Resource\Resource` — базовый класс административной сущности.
- `MB\Bitrix\AdminKit\Resource\CrudResource` — CRUD DSL без встроенного persistence (`hasCrud(): false`).
- `MB\Bitrix\AdminKit\Resource\DataManagerResource` — D7 ORM CRUD (`hasCrud(): true`).

## DataManagerResource

Обязательный метод:

- `dataManagerClass(): string` — вернуть класс `DataManager`.

Полезные public API для EntityObject flow:

- `queryObject(array $select = ['*'])`
- `findObject(mixed $id, array $select = ['*'])`
- `newObject(): object`

## Identity / menu methods

| Метод | Тип | Возвращает | Где используется | Когда использовать |
|---|---|---|---|---|
| `getId()` | public API (static) | `string` | registry/menu/url | Кастомный id |
| `getTitle()` | public API | `string` | заголовки/menu | Пользовательское имя ресурса |
| `getSort()` | public API (static) | `int` | menu sort | Порядок в меню |
| `getMenuIcon()` | public API (static) | `string` | menu icon | Иконка меню |
| `isVisibleInMenu()` | public API (static) | `bool` | menu | Скрыть ресурс в меню |
| `getParentMenuId()` | public API (static) | `?string` | menu hierarchy | Группировка меню |
| `group()` | public API | `?string` | menu/display | Доп. группировка |

## Fields methods

| Метод | Тип | Возвращает | Где используется | Когда использовать |
|---|---|---|---|---|
| `fields()` | extension point (`protected`) | `iterable` | fallback | Общий набор полей |
| `indexFields()` | public API | `iterable` | IndexPage | Поля списка |
| `formFields()` | public API | `iterable` | FormPage | Поля формы |
| `detailFields()` | public API | `iterable` | DetailPage | Поля просмотра |
| `formTabs()` | public API | `iterable` | FormPage | Табы формы |

## Filters / actions / pages

| Метод | Тип | Возвращает | Где используется | Когда использовать |
|---|---|---|---|---|
| `filters()` | public API | `iterable` | IndexPage/filter | Фильтрация |
| `searchColumns()` | public API | `array` | быстрый поиск тулбара | Колонки для строки поиска (FIND) |
| `rowActions()` | public API | `iterable` | row actions | Действия строки |
| `bulkActions()` | public API | `iterable` | bulk panel | Массовые действия |
| `asyncActions()` | public API | `iterable` | async handlers | AJAX действия |
| `toolbarActions()` | public API | `iterable` | index toolbar | Кастомные кнопки toolbar (`ToolbarAction`) |
| `showCreateButton()` | public API | `bool` | index toolbar | Показывать стандартную кнопку «Создать» (default `true`) |
| `createButtonLabel()` | public API | `?string` | index toolbar | Своя подпись стандартной кнопки «Создать» |
| `pages()` | public API | `iterable` | page resolver | Кастомный набор страниц |

## Toolbar (фичи поверх кнопок)

Дополнительные возможности нативного тулбара (`ResourceToolbarContract` / `HasResourceToolbar`), маппятся на фасад `Bitrix\UI\Toolbar\Facade\Toolbar` в `ToolbarRenderer`. Все по умолчанию выключены.

| Метод | Возвращает | Маппинг | Когда использовать |
|---|---|---|---|
| `toolbarTitle()` | `?string` | `Toolbar::setTitle()` | Переопределить заголовок тулбара |
| `toolbarEditableTitle()` | `bool` | `Toolbar::addEditableTitle()` | Редактируемый заголовок *(UI-only)* |
| `toolbarFavoriteStar()` | `bool` | `Toolbar::addFavoriteStar()` | Звезда «в избранное» *(UI-only)* |
| `toolbarCopyLink()` | `?array` | `Toolbar::setCopyLinkButton()` | Кнопка «копировать ссылку» (ключи `link`/`successfulCopyMessage`/`title`) |
| `toolbarBeforeTitleHtml()` | `?string` | `Toolbar::addBeforeTitleHtml()` | HTML перед заголовком |
| `toolbarAfterTitleHtml()` | `?string` | `Toolbar::addAfterTitleHtml()` | HTML после заголовка |
| `toolbarUnderTitleHtml()` | `?string` | `Toolbar::addUnderTitleHtml()` | HTML под заголовком |
| `toolbarRightHtml()` | `?string` | `Toolbar::addRightCustomHtml()` | HTML в правой части тулбара |

> *UI-only*: `toolbarEditableTitle()` и `toolbarFavoriteStar()` только отрисовывают UI нативного тулбара; серверное сохранение нового заголовка / добавление в избранное в этой итерации не подключается — это ответственность интегратора.

## Grid (флаги и режимы)

Настройки нативного `bitrix:main.ui.grid` (`ResourceGridContract` / `HasResourceGrid` → `GridSettings` → `BitrixGridAdapter`). Дефолты совпадают с прежним поведением.

| Метод | Возвращает | Маппинг (param) | Дефолт |
|---|---|---|---|
| `allowColumnsSort()` | `bool` | `ALLOW_COLUMNS_SORT` | `true` |
| `allowColumnsResize()` | `bool` | `ALLOW_COLUMNS_RESIZE` | `true` |
| `allowHorizontalScroll()` | `bool` | `ALLOW_HORIZONTAL_SCROLL` | `true` |
| `allowRowsSort()` | `bool` | `ALLOW_ROWS_SORT` | `false` *(см. ниже)* |
| `allowContextMenu()` | `bool` | `ALLOW_CONTEXT_MENU` | `false` |
| `pinHeader()` | `bool` | `ALLOW_PIN_HEADER` | `false` |
| `stickedColumns()` | `bool` | `ALLOW_STICKED_COLUMNS` | `false` |
| `showGridSettingsMenu()` | `bool` | `SHOW_GRID_SETTINGS_MENU` | `true` |
| `enableFieldsSearch()` | `bool` | `ENABLE_FIELDS_SEARCH` | `false` |
| `showSelectedCounter()` | `bool` | `SHOW_SELECTED_COUNTER` | `true` |
| `showTotalCounter()` | `bool` | `SHOW_TOTAL_COUNTER` | `true` |
| `useAjax()` | `bool` | `AJAX_MODE` (`Y`/`N`) | `true` |
| `pageSizes()` | `int[]` | `PAGE_SIZES` + `SHOW_PAGESIZE` | `[]` (селектор скрыт) |
| `gridEmptyMessage()` | `?string` | `STUB` (текст пустого грида) | `null` |
| `gridAggregates()` | `array` | `AGGREGATE` | `[]` |
| `gridFooter()` | `array` | `FOOTER` | `[]` |
| `tileMode()` | `bool` | `TILE_GRID_MODE` | `false` |
| `tileSize()` | `?string` | `TILE_SIZE` | `null` |
| `tileItemJsClass()` | `?string` | `JS_CLASS_TILE_GRID_ITEM` | `null` |
| `rowLayout()` | `?string` | `ROW_LAYOUT` | `null` |
| `sortField()` | `?string` | — (поле для drag-sort) | `null` |
| `sortStep()` | `int` | — (шаг инкремента SORT) | `100` |
| `reorder(array $orderedIds)` | `void` | — (сохранение порядка) | DataManager update по `sortField()` |

### Drag-сортировка строк

1. `allowRowsSort(): true` — включает перетаскивание строк в гриде.
2. `sortField(): 'SORT'` — поле, в которое пишется порядок.

При drag-end JS-расширение (`MB.AdminKit.GridRowSort`) ловит событие `Grid::rowMoved` и POST-ит порядок на `action=rowsort`; `IndexRowSortHandler` вызывает `reorder($orderedIds)`. Дефолтный `reorder()` пишет в `sortField()` инкрементальные значения с шагом `sortStep()` (по умолчанию 100: `100,200,…`) напрямую через DataManager (минуя form-pipeline). Переопределите `reorder()` для своей логики.

```php
public function allowRowsSort(): bool { return true; }
public function sortField(): ?string { return 'SORT'; }
public function sortStep(): int { return 10; } // шаг сортировки (опционально, default 100)
```

> Без `sortField()` (null) перетаскивание не активируется — JS-инициализация не подключается.
>
> **Перенос между группами**: для сгруппированного грида (`indexGrouping()`) обработчик восстанавливает целевую группу каждого элемента по маркерам `group:` в payload и передаёт их в `reorder()` (аргументы `$groupByItemId`, `$groupField` = FK группировки). Дефолтный `reorder()` при этом обновляет и порядок (`sortField`), и принадлежность к группе (FK) — перетаскивание строки в другую группу реально меняет её группу.

#### Ограничения дефолтного `reorder()`

- **Несовместимо с пагинацией.** JS отправляет только строки текущей страницы, а `reorder()` всегда нумерует с `100`, не зная номера страницы. При включённой пагинации (`showPagination() !== false`) перетаскивание на 2-й и далее страницах перезапишет SORT значениями `100,200,…`, которые столкнутся с диапазоном первой страницы → межстраничный порядок «перемешается». Также нельзя перетащить строку между страницами. **Используйте drag-sort только при `showPagination(): false`** (все записи на одной странице).
- **Переписывает SORT у всех видимых строк.** Каждый drag выполняет по одному `UPDATE` на каждую отрендеренную строку (без батчинга/diff) и нормализует значения в `sortStep(), 2×sortStep(), …` — существующие значения и промежутки SORT теряются. Для больших страниц (`maxPageSize`) это много запросов на один drag.
- **Без транзакции и form-pipeline.** Апдейты независимы (ошибки проглатываются — возможен частичный результат) и идут напрямую через `DataManager::update()` минуя валидацию и resource-хуки (`afterUpdate` и т.п.); ORM-события сущности при этом срабатывают.
- **Только целочисленные PK** (`(int)$id`); строковые/UUID-ключи пропускаются.
- **Права** проверяются один раз (`canUpdate` на ресурс), не по каждой строке; нет блокировок при одновременном редактировании.
- Группа `__ungrouped` FK не меняет.

Для иных стратегий (интерполяция между соседями, батч в транзакции, поддержка пагинации) переопределите `reorder()`.

## Query / Grid methods

| Метод | Тип | Возвращает | Где используется | Когда использовать |
|---|---|---|---|---|
| `defaultSort()` | public API | `array` | grid defaults | Сортировка по умолчанию |
| `defaultFilter()` | public API | `array` | grid defaults | Фильтр по умолчанию |
| `defaultSelect()` | public API | `array` | query | Select по умолчанию |
| `runtimeFields()` | public API | `array` | query runtime | Runtime поля |
| `indexSelect()` / `indexFilter()` / `indexOrder()` / `indexRuntime()` | public API | `array` | GridQueryBuilder | Настройка ORM-параметров index |
| `beforeIndexQueryParams()` / `modifyIndexParams()` | public API | `array` | перед `DataManager::getList()` | Финальная корректировка параметров |
| `afterIndexRows()` / `mapIndexRow()` | public API | `array` | после выборки | Постобработка строк |
| `getGridId()` / `getFilterId()` | public API | `string` | Bitrix UI IDs | Кастомные ID grid/filter |

## Permissions / sidepanel / export / performance

| Метод | Тип | Возвращает | Где используется | Когда использовать |
|---|---|---|---|---|
| `canView()` / `canCreate()` / `canUpdate()` / `canDelete()` | public API | `bool` | pages/actions/persistence | Контроль доступа |
| `useSidePanel()` / `createInSidePanel()` / `editInSidePanel()` / `detailInSidePanel()` | public API | `bool` | router/ui | Режим sidepanel |
| `sidePanelWidth()` / `closeSidePanelAfterSave()` | public API | `int`/`bool` | sidepanel UX | Параметры sidepanel |
| `exportEnabled()` | public API | `bool` | toolbar/bulk/export endpoints | Главный выключатель экспорта (default `false`) |
| `allowExportByFilter()` / `allowExportAll()` / `maxExportRows()` | public API | `bool`/`int` | export handler | Политика экспорта (учитывается, когда экспорт включён) |
| `showPagination()` | public API | `bool` | grid | Показывать пагинацию (default `true`; `false` — все записи на одной странице, панель навигации скрыта) |
| `useTotalCount()` / `countCacheTtl()` / `maxPageSize()` / `bulkChunkSize()` | public API | `bool`/`int` | grid/performance | Лимиты/оптимизация |

## Lifecycle hooks

`beforeValidate`, `afterValidate`, `beforeCreate`, `afterCreate`, `beforeUpdate`, `afterUpdate`, `beforeDelete`, `afterDelete`, `beforeMassDelete`, `afterMassDelete` — public extension points, вызываются в persistence flow.

## Не пользовательский API

- Runtime/internal helpers вроде `getPages()`, `indexPage()`, `formPage()`, `detailPage()` используйте только как runtime-access, а не как DSL-конфигурацию.
- Низкоуровневые менеджеры/роутер (`AdminKitRegistry`, `AdminKitRouter`) не являются ежедневным API для описания ресурса.
