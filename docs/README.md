# Documentation

Карта пользовательской документации `mb4it/bitrix-admin-kit`.

## Start here

- [Installation](installation.md) — установка пакета и базовое подключение в Bitrix-проекте.
- [Quick Start](quick-start.md) — первый CRUD-раздел: Resource + Grid + Form + Filter + Actions.

## Main concepts

- [Resources](resources.md) — что такое Resource и как описывать CRUD-раздел.
- [Pages](pages.md) — как работают `IndexPage`, `FormPage`, `DetailPage` и standalone-страницы.
- [Fields](fields.md) — обзор Field API и практические примеры полей.
- [Filters](docs/user/reference/filters.md) — фильтры для `main.ui.filter` и ORM-фильтрации.
- [Grid](grid.md) — работа с `bitrix:main.ui.grid` через Admin Kit.
- [Actions](actions.md) — RowAction и сценарии пользовательских действий.
- [Bulk actions](bulk-actions.md) — массовые действия с безопасным запуском.

## Standalone pages

- [OptionsPage](options-page.md) — страница настроек в админке.
- [DashboardPage](dashboard-page.md) — dashboard-страницы и виджеты.
- [CustomPage](docs/user/reference/pages.md) — произвольные standalone-страницы.

## Additional features

- [Discovery & Routing](docs/user/guides/discovery-routing.md) — автопоиск классов и маршрутизация.
- [UI integration](docs/user/guides/ui-integration.md) — интеграция с Bitrix UI extensions, toolbar, sidepanel.
- [Import / Export](docs/user/guides/import-export.md) — CSV-first импорт/экспорт и ограничения UI.
- [Permissions](docs/user/guides/permissions.md) — контроль доступа к действиям и страницам.

## Advanced

- [Architecture](docs/dev/architecture.md) — обзор архитектурных компонентов.
- [Forms lifecycle](docs/user/guides/forms-lifecycle.md) — этапы FormData/DataPipeline и сохранение.
- [Performance & diagnostics](docs/user/guides/performance-diagnostics.md) — QueryGuard, total count, DB health.
- [Backward compatibility](docs/dev/backward-compatibility.md) — стабильные API и миграционные заметки.

## Cookbook

- [Cookbook](docs/user/cookbook/README.md) — короткие прикладные рецепты по типовым сценариям.
