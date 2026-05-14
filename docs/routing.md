# Routing v0.8.0

`AdminKitManager` delegates routing to `AdminKitRouter`. The router resolves `?page=` to a standalone page first, then to a CRUD resource. If no `page` parameter is present, the first registered resource or page is used.

Build URLs through `MB\Bitrix\AdminKit\Support\UrlGenerator`:

- `resourceUrl()` / `pageUrl()` for list and standalone pages
- `createUrl()`, `editUrl()`, `detailUrl()` for CRUD pages
- `actionUrl()`, `bulkActionUrl()`, `importUrl()`, `exportUrl()`, `endpointUrl()` for endpoints
