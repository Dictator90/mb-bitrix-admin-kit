# Маршрутизация v0.8.0

`AdminKitManager` делегирует маршрутизацию `AdminKitRouter`. Роутер разрешает `?page=` сначала в standalone-страницу, затем в CRUD-ресурс. Если параметр `page` отсутствует, используется первый зарегистрированный ресурс или страница.

Собирайте URL через `MB\Bitrix\AdminKit\Support\UrlGenerator`:

- `resourceUrl()` / `pageUrl()` — список и standalone-страницы;
- `createUrl()`, `editUrl()`, `detailUrl()` — CRUD-страницы;
- `actionUrl()`, `bulkActionUrl()`, `importUrl()`, `exportUrl()`, `endpointUrl()` — эндпоинты.
