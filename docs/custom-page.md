# CustomPage v0.8.0

Extend `CustomPage` for arbitrary non-CRUD admin pages. Override `content()` and optionally `toolbarActions()` and `canView()`.

The page is rendered in the AdminKit layout with BEM classes (`adminkit-page`, `adminkit-toolbar`, `adminkit-page__content`) and can load Bitrix UI extensions through `$extensions`.
