# Actions

Actions describe user-triggered operations in row menus, toolbars, async endpoints, import/export controls, and bulk panels.

## Row actions

Use existing row actions for view, edit, delete, and custom callbacks. Destructive actions must check permissions and CSRF.

## Stable API

The base Action API is stable for v1.x: public/protected method signatures, class names, namespaces, visibility conditions, labels, confirmation flags, and handler semantics must remain backward-compatible.
