# Reference: Pages

## Resource pages

- `MB\Bitrix\AdminKit\Page\IndexPage` (алиас `MB\Bitrix\AdminKit\Page\Crud\IndexPage`)
- `MB\Bitrix\AdminKit\Page\FormPage` (алиас `MB\Bitrix\AdminKit\Page\Crud\FormPage`)
- `MB\Bitrix\AdminKit\Page\DetailPage` (алиас `MB\Bitrix\AdminKit\Page\Crud\DetailPage`)

## Standalone pages

- `MB\Bitrix\AdminKit\Page\Standalone\OptionsPage`
- `MB\Bitrix\AdminKit\Page\Standalone\DashboardPage`
- `MB\Bitrix\AdminKit\Page\Standalone\CustomPage`

## Common page API

| Метод | Класс/тип страницы | Возвращает | Что делает | Когда использовать |
|---|---|---|---|---|
| `render()` | все | `void` | рендер экрана | Основная точка вывода |
| `title()` | `Page`, `ResourcePage`, `StandalonePage` | `string` | заголовок | Кастомный title |
| `canView(?PermissionContext $context)` | resource/standalone | `bool` | проверка доступа | Ограничение доступа |
| `url(array $params = [])` | standalone | `string` | URL страницы | Ссылки на standalone |
| `id()`, `sort()`, `icon()`, `group()` | standalone | scalar/nullable | identity/menu | Настройка standalone меню |

## IndexPage API

| Метод | Тип | Возвращает | Что делает | Когда использовать |
|---|---|---|---|---|
| `definition()` | public API | `IndexPageDefinitionContract` | источник fields/filters/query | При полной подмене definition |
| `fields()` | public API | `iterable` | колонки списка | Точечная кастомизация полей |
| `bulkActions()` | public API | `iterable` | bulk-панель | Кастомные массовые действия |
| `buildGrid()` | public API | `Grid` | сборка grid | Глубокая кастомизация grid |
| `isForAllRowsSelected()` / `resolveSelectedIds()` | public API | `bool`/`array` | bulk selection | В кастомном bulk flow |

## FormPage API

| Метод | Тип | Возвращает | Что делает | Когда использовать |
|---|---|---|---|---|
| `fields()` | public API | `iterable` | поля формы | Переопределить набор полей |
| `getId()` / `setId()` | runtime API | mixed/void | id текущей записи | Runtime state |
| `getFormMode()` / `setFormMode()` | runtime API | string/void | режим `create`/`edit` | Runtime state |
| `beforeSave()` / `afterSave()` | extension point (`protected`) | `void` | hooks сохранения | Доп. бизнес-логика |
| `redirectAfterSave()` | extension point (`protected`) | `?string` | редирект после save | Кастомный post-save UX |

## DetailPage API

| Метод | Тип | Возвращает | Что делает | Когда использовать |
|---|---|---|---|---|
| `fields()` | public API | `iterable` | поля просмотра | Read-only представление |
| `render()` | public API | `void` | вывод detail | Кастомный шаблон/обертка |

## OptionsPage / DashboardPage / CustomPage API

| Метод | Класс | Возвращает | Что делает | Когда использовать |
|---|---|---|---|---|
| `fields()` | `OptionsPage` | `iterable` | поля опций | Простая options-форма |
| `components()` | `OptionsPage` | `iterable` | layout-компоненты | Сложная layout-форма |
| `widgets()` | `DashboardPage` | `iterable` | виджеты dashboard | Dashboard с карточками |
| `content()` | `CustomPage` | `string` | контент страницы | Кастомный HTML/UI |

## Namespace aliases

- Рекомендуемый namespace в документации: `MB\Bitrix\AdminKit\Page\...`.
- CRUD-классы в `Page\Crud\...` существуют и поддерживаются как алиасы/наследники.
- Namespace `MB\Bitrix\AdminKit\Pages\*` не использовать.
