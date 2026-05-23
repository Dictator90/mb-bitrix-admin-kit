# Архитектура (dev)

Ключевые принципы:
- `GridQueryBuilder` — единственный источник ORM query params.
- `GridDataLoader` — исполнение запросов, total count, caching, guard.
- `IndexPage`/`FormPage`/`DetailPage` — UI-слой, не ORM-конструктор.
- `AdminKitManager` — фасад для router/registry/menu/renderer.
- `ClassDiscovery` — единственное место class-discovery.

Перед изменениями public API сверяйтесь с BC-политикой.
