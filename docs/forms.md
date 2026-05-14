# Forms

Forms render `formFields()`, normalize incoming request data through Field objects, validate values, run lifecycle hooks, check permissions, and persist through `CrudPersister`.

## FormData

`FormData` is stage-aware and keeps separate raw, normalized, validated, and errors data. Its format is stable for v1.x and must not be changed in minor or patch releases.

## Validation and saving

Use Field `normalize()` and validation APIs for all create/edit/import flows. Avoid duplicating import-only normalization rules when the same behavior belongs to a Field.
