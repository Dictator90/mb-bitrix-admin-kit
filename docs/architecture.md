# Architecture

## AdminKitManager

Публичный фасад модуля. Регистрирует Resource и standalone Page, отдает меню, текущую страницу, router и renderer. В v0.8+ фасад делегирует хранение, маршрутизацию и рендеринг специализированным классам.

## Registry

`Manager\AdminKitRegistry` хранит зарегистрированные Resource/Page и может выполнять discovery по `lib/Admin`. Registry не рендерит и не строит URL.

## Router

`Manager\AdminKitRouter` читает request, ищет `?page=`, выбирает standalone Page или ResourcePage. Если page не найден, возвращает NotFoundPage.

## Resource

`Resource` — базовое описание административного раздела: id, title, menu, permissions, default select/filter/sort, runtime fields, hooks и SidePanel настройки.

## CrudResource

`CrudResource` — Resource для Bitrix D7 ORM `DataManager`. Он требует `dataManagerClass()`, `indexFields()` и `formFields()`, добавляет CRUD defaults, bulk chunk size, import/export лимиты и параметры производительности.

## Page

`Page\IndexPage`, `FormPage`, `DetailPage` обслуживают CRUD. `Pages\OptionsPage`, `CustomPage`, `DashboardPage` обслуживают самостоятельные страницы модуля.

## Field

Field описывает колонку, форму, нормализацию, валидацию, экспорт/импорт и отображение. Конкретные Field-классы расширяют базовый `Field`; массивы в multiple-полях сохраняются явно.

## Filter

Filter описывает поле `main.ui.filter` и преобразует непустое UI-значение в ORM filter. Для сложных сценариев используйте `CallbackFilter` или Resource hooks.

## Action

Row actions формируют меню строки. Bulk actions исполняются через `BulkOperationContext`, возвращают `BulkResult`, проверяют выбранные ID, `canRun()` и права Resource по каждой записи.

## GridQueryBuilder

Собирает ORM-параметры списка из полей, фильтров, сортировки, пагинации, default/index hooks, runtime fields и `modifyIndexParams()`.

## CrudPersister

Единая точка create/update/delete для ORM. Возвращает `DbResult`, чтобы низкоуровневые ORM-ошибки доходили до формы и bulk operations.

## FormData

Stage-aware контейнер формы: `raw`, `normalized`, `validated`, `errors`. Используется формой и import pipeline, чтобы CSV-import и ручное сохранение имели одинаковую нормализацию.

## UrlGenerator

Строит admin URL для Resource, Page, create/edit/detail, row action, bulk action, import/export и endpoint-сценариев. Новая page/menu/routing логика не должна конкатенировать query string вручную.

## Support adapters

`AdminCollection`, `AdminString`, `AdminCondition` изолируют ядро AdminKit от глобальных helper-функций и конкретных реализаций support-пакетов.
