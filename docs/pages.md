# Pages

## Что это

`Page` — это конкретный экран админки. `Resource` описывает сущность, а `Page` отвечает за отображение конкретного сценария: список, форма, просмотр, options/dashboard/custom.

## Когда использовать

- Нужно изменить отображение index/form/detail.
- Нужно заменить стандартную CRUD-страницу.
- Нужна standalone-страница (`OptionsPage`, `DashboardPage`, `CustomPage`).

## Типы страниц

| Тип | Класс | Когда использовать |
|---|---|---|
| IndexPage | `MB\Bitrix\AdminKit\Page\IndexPage` (`Page\Crud\IndexPage`) | список записей |
| FormPage | `MB\Bitrix\AdminKit\Page\FormPage` (`Page\Crud\FormPage`) | создание/редактирование |
| DetailPage | `MB\Bitrix\AdminKit\Page\DetailPage` (`Page\Crud\DetailPage`) | просмотр записи |
| OptionsPage | `MB\Bitrix\AdminKit\Page\Standalone\OptionsPage` | настройки |
| DashboardPage | `MB\Bitrix\AdminKit\Page\Standalone\DashboardPage` | dashboard |
| CustomPage | `MB\Bitrix\AdminKit\Page\Standalone\CustomPage` | произвольный экран |

> Используйте namespace `MB\Bitrix\AdminKit\Page\*`. Namespace `MB\Bitrix\AdminKit\Pages\*` в пакете отсутствует.

## CRUD pages

### IndexPage

Используется для списка. Обычно берет fields/filters/actions/query hooks из `CrudResource` через `ResourceBackedIndexPageDefinition`.

### FormPage

Используется для create/edit. Для `DataManagerResource` обрабатывает сохранение через ORM EntityObject.

### DetailPage

Используется для просмотра записи (read-only), поля обычно берутся из `detailFields()`.

## Standalone pages

- `OptionsPage` — для настроек (подробнее: [OptionsPage](options-page.md)).
- `DashboardPage` — для dashboard/виджетов (подробнее: [DashboardPage](dashboard-page.md)).
- `CustomPage` — для произвольного HTML/компонентного контента.

## Resource pages vs Standalone pages

- Resource pages принадлежат ресурсу и участвуют в CRUD-routing.
- Standalone pages — самостоятельные пункты/экраны.
- Подклассы CRUD-страниц ресурса не становятся standalone-пунктами меню автоматически.

## Практические сценарии

- Переопределить index page: вернуть свою страницу в `pages()` ресурса.
- Ресурс без detail: не добавлять detail page в `pages()`.
- Добавить OptionsPage рядом с CRUD: зарегистрировать standalone page отдельно.
- Добавить DashboardPage для метрик.
- Добавить CustomPage под спец-сценарий.

## Связанные разделы

- [Reference: Pages](user/reference/pages.md)
- [Reference: Resources](user/reference/resources.md)
- [First standalone page](user/getting-started/first-standalone-page.md)
- [Discovery & routing](user/guides/discovery-routing.md)
