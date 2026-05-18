# Backward compatibility policy

v1.0.0 freezes the stable public API for AdminKit. Minor and patch releases in v1.x must not break existing modules.

## Must remain compatible

- Public/protected method signatures.
- Public class names and namespaces.
- Basic CRUD behavior.
- `FormData` raw/normalized/validated/errors format.
- `GridContext` format.
- `DbResult` format.
- `BulkResult` format.
- Base Field API.
- Base Filter API.
- Base Action API.
- Resource and CrudResource extension points.
- Support adapter class names: `AdminCollection`, `AdminString`, `AdminCondition`.

## Deprecation rules

A public API removal must be introduced with `@deprecated` phpdoc and migration notes before it is removed. Internal adapters can change, but module authors should not be forced to instantiate them directly.

## Allowed changes in minor releases

- New optional methods with safe defaults.
- New Field/Filter/Action classes.
- New documentation and examples.
- Bug fixes that preserve the documented contracts.

## v1.x stabilization notes (2026-05)

- Legacy CRUD page aliases are preserved as wrappers:
  - `MB\Bitrix\AdminKit\Page\IndexPage` -> `Page\Crud\IndexPage`
  - `MB\Bitrix\AdminKit\Page\FormPage` -> `Page\Crud\FormPage`
  - `MB\Bitrix\AdminKit\Page\DetailPage` -> `Page\Crud\DetailPage`
- `Resource` keeps backward-compatible CRUD page helper behavior (`indexPage`, `formPage`, `detailPage`) and safe defaults used by legacy non-CRUD resources.
- DataManager fallback remains compatible for legacy resources that extend `Resource` and wire persistence traits directly.
- JSON/early-response branches are test-aware to avoid silent PHPUnit process aborts; production behavior still terminates responses normally.

## Documented exception (UI-layer refactor)

This release intentionally introduces breaking API changes in UI contracts:

- Removed `MB\Bitrix\AdminKit\Contracts\FieldContract` and `MB\Bitrix\AdminKit\Contracts\ComponentContract`.
- Replaced with narrow contracts in `MB\Bitrix\AdminKit\Contracts\Field\*`, `MB\Bitrix\AdminKit\Contracts\UI\*`, and `MB\Bitrix\AdminKit\Contracts\Widget\*`.
- `GraphWidget` remains available as a compatibility alias for `ChartWidget`, but chart assets are now local-extension driven (no CDN injection from PHP).

Migration path:

1. Update imports from old contracts to the new namespace groups.
2. Type against `UI\ComponentContract` for simple renderables and `UI\LayoutComponentContract` for containers.
3. Use `Field\FieldContract` (aggregate) or narrower field contracts where possible.
4. Replace manual `mb.ui.tabs` / `mb.ui.dialog-selector` loads with `mb.admin.kit` exports (`kit.Tabs`, `kit.DialogSelector`).
