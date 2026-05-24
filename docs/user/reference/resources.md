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
| `rowActions()` | public API | `iterable` | row actions | Действия строки |
| `bulkActions()` | public API | `iterable` | bulk panel | Массовые действия |
| `asyncActions()` | public API | `iterable` | async handlers | AJAX действия |
| `toolbarActions()` | public API | `iterable` | index toolbar | Кнопки toolbar |
| `pages()` | public API | `iterable` | page resolver | Кастомный набор страниц |

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
| `allowExportByFilter()` / `allowExportAll()` / `maxExportRows()` | public API | `bool`/`int` | export handler | Политика экспорта |
| `useTotalCount()` / `countCacheTtl()` / `maxPageSize()` / `bulkChunkSize()` | public API | `bool`/`int` | grid/performance | Лимиты/оптимизация |

## Lifecycle hooks

`beforeValidate`, `afterValidate`, `beforeCreate`, `afterCreate`, `beforeUpdate`, `afterUpdate`, `beforeDelete`, `afterDelete`, `beforeMassDelete`, `afterMassDelete` — public extension points, вызываются в persistence flow.

## Не пользовательский API

- Runtime/internal helpers вроде `getPages()`, `indexPage()`, `formPage()`, `detailPage()` используйте только как runtime-access, а не как DSL-конфигурацию.
- Низкоуровневые менеджеры/роутер (`AdminKitRegistry`, `AdminKitRouter`) не являются ежедневным API для описания ресурса.
