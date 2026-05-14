# Filters

Filters define Bitrix `main.ui.filter` metadata and ORM filter application rules.

## Built-in filters

- `TextFilter` for exact/contains/starts-with/ends-with string conditions.
- `NumberFilter` for exact, range, greater-than, and less-than conditions.
- `SelectFilter` for list filters.
- `DateFilter` for exact and range date conditions.
- `CheckboxFilter` for boolean-like values.
- `CallbackFilter` for custom Resource-owned ORM filter logic.

Empty values are skipped by grid processing, while meaningful values such as `0`, `'0'`, and `false` are preserved.

## Stable API

The base Filter API, filter class names, namespaces, and public/protected signatures are backward-compatible in minor and patch releases.
