# Pages v0.8.0

AdminKit v0.8.0 treats standalone pages as first-class admin module pages. Extend `MB\Bitrix\AdminKit\Pages\CustomPage`, `OptionsPage`, or `DashboardPage` and register it through `AdminKitManager::registerPage()` or module discovery.

Every standalone page exposes the unified instance API:

- `id(): string`
- `title(): string`
- `sort(): int`
- `icon(): ?string`
- `group(): ?string`
- `canView(PermissionContext $context): bool`
- `render()`
- `url(array $params = []): string`

The legacy static API (`getId()`, `getTitle()`, `getSort()`, `getMenuIcon()`, `getParentMenuId()`) remains supported.


## Responsibility map

- Current page resolution: `AdminKitRouter` reads request `page` / `action` parameters and returns either a standalone page, a resource page wrapper, or a not-found page.
- URL building: `UrlGenerator` owns page, resource, CRUD, action, bulk, import, and export URLs.
- Menu building: `AdminKitMenuBuilder` reads `AdminKitRegistry`, applies visibility and permission checks, groups, sorts, and returns Bitrix menu arrays.
- Toolbar rendering: standalone pages can return `ToolbarAction` objects; CRUD pages keep their existing toolbar rendering and can migrate actions incrementally.
- SidePanel behavior: `SidePanelAdapter` owns iframe parameters, slider opening JavaScript, close-after-save behavior, and grid refresh hooks.
- Asset loading: `AssetManager` owns Bitrix extension/CSS/JS registration for page-layer code.
- Rendering current page: `AdminKitRenderer` captures `render()` output for manager-driven rendering.
