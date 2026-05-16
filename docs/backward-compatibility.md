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

## Documented exception (UI-layer refactor)

This release intentionally introduces breaking API changes in UI contracts:

- Removed `MB\Bitrix\AdminKit\Contracts\FieldContract` and `MB\Bitrix\AdminKit\Contracts\ComponentContract`.
- Replaced with narrow contracts in `MB\Bitrix\AdminKit\Contracts\Field\*`, `MB\Bitrix\AdminKit\Contracts\UI\*`, and `MB\Bitrix\AdminKit\Contracts\Widget\*`.
- `GraphWidget` remains available as a compatibility alias for `ChartWidget`, but chart assets are now local-extension driven (no CDN injection from PHP).

Migration path:

1. Update imports from old contracts to the new namespace groups.
2. Type against `UI\ComponentContract` for simple renderables and `UI\LayoutComponentContract` for containers.
3. Use `Field\FieldContract` (aggregate) or narrower field contracts where possible.
