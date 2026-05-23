# Reference: Pages

## Standalone

- `MB\Bitrix\AdminKit\Page\Standalone\CustomPage`
- `MB\Bitrix\AdminKit\Page\Standalone\OptionsPage`
- `MB\Bitrix\AdminKit\Page\Standalone\DashboardPage`

## Resource pages

- `MB\Bitrix\AdminKit\Page\IndexPage` (алиас `Page\Crud\IndexPage`)
- `MB\Bitrix\AdminKit\Page\FormPage` (алиас `Page\Crud\FormPage`)
- `MB\Bitrix\AdminKit\Page\DetailPage` (алиас `Page\Crud\DetailPage`)

## Stable instance API standalone-страниц

- `id()`, `title()`, `sort()`, `icon()`, `group()`
- `canView(PermissionContext $context)`
- `render()`
- `url(array $params = [])`

## Ограничения

- Не используйте несуществующий namespace `MB\Bitrix\AdminKit\Pages\*`.
- Resource-page subclasses не становятся standalone-пунктами меню автоматически.
