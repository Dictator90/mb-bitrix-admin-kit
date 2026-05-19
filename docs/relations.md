# Relations

Relation fields live under `MB\Bitrix\AdminKit\Field\Relation`.

- `BelongsTo`: Reference relation, single related entity.
- `HasOne`: one related entity (embedded form support is limited).
- `HasMany`: collection relation (table preview support, embedded editing is limited).
- `BelongsToMany`: many-to-many relation with two modes:
  - legacy CSV storage (`storedAsCsv()`),
  - ORM relation mode (`relation()/relatedTable()/pivotTable()/saveUsingOrm()`).

Runtime explicit relations are registered through `RuntimeRelationRegistrar` + `RuntimeRelationBuilder` and build Bitrix ORM fields (`Reference`, `OneToMany`, `ManyToMany`).

Current limitations:
- Runtime `ManyToMany` requires explicit pivot keys (`foreignPivotKey`, `relatedPivotKey`).
- Advanced nested CRUD for `HasMany` remains intentionally limited in this iteration.
