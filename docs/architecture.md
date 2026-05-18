# Architecture

## AdminKitManager

Публичный фасад модуля. Регистрирует Resource и standalone Page, отдает меню, текущую страницу, router и renderer. В v0.8+ фасад делегирует хранение, маршрутизацию и рендеринг специализированным классам.

## Registry

`Manager\AdminKitRegistry` хранит зарегистрированные Resource/Page и выполняет discovery через `Discovery\ClassDiscovery` и `mb4it/filesystem` `ClassFinder` (Reflection-based checks for final Resource/standalone Page classes). Registry не рендерит и не строит URL.

## Router

`Manager\AdminKitRouter` читает request, ищет `?page=`, выбирает standalone Page или ResourcePage. Если page не найден, возвращает NotFoundPage.

## Resource

`Resource` — базовое описание административного раздела: id, title, menu, permissions, pages, SidePanel-настройки и совместимые CRUD-хелперы. Существующие ресурсы могут продолжать наследовать `Resource` напрямую.

`ResourceContract` остаётся агрегатным контрактом для обратной совместимости. Узкие контракты (`ResourceIdentityContract`, `IndexResourceContract`, `FormResourceContract`, `ExportResourceContract` и др.) позволяют постепенно сужать зависимости внутренних классов без поломки публичного API.

## CrudResource

`CrudResource` extends `Resource` и является рекомендуемой базой для новых Bitrix D7 ORM CRUD-разделов. Он требует `dataManagerClass()` и наследует defaults (`defaultSort`, `maxPageSize`, export limits, bulk chunk size и т.д.) из `Resource` без дублирования.

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

Stage-aware контейнер формы: `raw`, `normalized`, `validated`, `errors`. Используется формой (и import pipeline, когда импорт будет снова включён), чтобы CSV-import и ручное сохранение имели одинаковую нормализацию.

## UrlGenerator

Строит admin URL для Resource, Page, create/edit/detail, row action, bulk action, import/export и endpoint-сценариев. Новая page/menu/routing логика не должна конкатенировать query string вручную.

## Support adapters

`AdminCollection`, `AdminString`, `AdminCondition` изолируют ядро AdminKit от глобальных helper-функций и конкретных реализаций support-пакетов.

`LocalizedMessage` централизует `Loc::getMessage()` для пользовательских строк в actions, pages, fields и UI providers.

## Naming glossary (омонимы)

| Имя | Namespace / путь | Роль |
|-----|------------------|------|
| `IndexPage` (legacy alias) | `Page\IndexPage` | Deprecated wrapper → `Page\Crud\IndexPage` |
| `IndexPage` (canonical) | `Page\Crud\IndexPage` | CRUD list page implementation |
| `OptionsPage` | `Pages\OptionsPage` | Standalone module settings (`b_option`) |
| `CustomPage` / `DashboardPage` | `Page\Standalone\*` | Other standalone pages |
| `ResourcePage` (page class) | `Page\ResourcePage` | Base class for resource-bound CRUD pages |
| `ResourcePage` (dispatcher) | `Manager\ResourcePage` | HTTP dispatcher: resolves index/form/detail for a resource |
| `HasOne` / `HasMany` (field) | `Field\HasOne`, `Field\HasMany` | Relation field types for forms/grid |
| `HasOne` / `HasMany` (component) | `Component\Relation\*` | Inline relation UI widgets on form/detail |
| `PageNotFoundException` | `Exception\PageNotFoundException` | Routing: page id not registered |
| `AdminKitException` | `Exceptions\AdminKitException` | General library errors |
