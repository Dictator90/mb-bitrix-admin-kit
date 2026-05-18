# Архитектура

## Общая модель

Пакет строится вокруг фасада менеджера и набора узких сервисов:

- регистрация и discovery классов;
- маршрутизация страниц;
- рендер CRUD/standalone страниц;
- загрузка данных грида и адаптация к Bitrix UI.

## Фасад и менеджеры

- `AdminKit`/`AdminKitManager` — точка входа для scope-модуля.
- `Manager\AdminKitRegistry` — хранение ресурсов и страниц, discovery через `Discovery\ClassDiscovery`.
- `Manager\AdminKitRouter` — выбор целевой страницы по запросу.
- `Manager\AdminKitMenuBuilder` — генерация меню.

## Ресурсы

- `Resource` — стабильная базовая модель раздела (id, title, menu, permissions, pages, sidepanel).
- `CrudResource` — CRUD DSL-слой.
- `DataManagerResource` — рекомендованная база для ORM-ресурсов.

Для нового ORM CRUD используйте `DataManagerResource`.

## Страницы

- CRUD: `Page\Crud\IndexPage`, `FormPage`, `DetailPage`.
- Standalone: `Pages\OptionsPage`, `Pages\DashboardPage`, `Page\Standalone\CustomPage`.

Совместимые алиасы `Page\IndexPage`, `Page\FormPage`, `Page\DetailPage` сохранены как обёртки.

## Поля, фильтры, действия

- `Field` — рендер, нормализация, валидация, отображение.
- `Filter` — перевод UI-фильтра в ORM-условия.
- `Action`/bulk actions — действия строки и массовые операции.

Bulk-операции работают через `BulkOperationContext` и возвращают `BulkResult`.

## Грид-слой

- `GridQueryBuilder` — единственный источник ORM-параметров (`select/filter/order/runtime/limit/offset`).
- `GridDataLoader` — выполнение запроса, total count, guard/caching.
- `Grid` + Bitrix-адаптеры — только UI-состояние и параметры компонентов.

Подробнее: [docs/grid.md](grid.md).

## Слой совместимости и поддержки

- `AdminCollection`, `AdminString`, `AdminCondition` — внутренние адаптеры support-пакетов.
- Public API должен оставаться стабильным в рамках v1.x (см. [docs/backward-compatibility.md](backward-compatibility.md)).

## Локализация

Пользовательские сообщения должны идти через `Bitrix\Main\Localization\Loc` с `lang/ru` и `lang/en` файлами.
