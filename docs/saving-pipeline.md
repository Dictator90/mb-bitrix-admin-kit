# Saving pipeline

Admin Kit v0.3.0 saves CRUD form data through a single pipeline:

1. read raw POST values;
2. normalize every editable field through `Field::normalize()`;
3. validate fields through `Field::runValidation()`;
4. run `CrudResource::beforeValidate()` and `CrudResource::afterValidate()`;
5. persist data through `CrudPersister`;
6. run create/update lifecycle hooks and Bitrix events;
7. show field-level validation errors or ORM errors when the operation fails.

`FormData` carries four explicit stages: `raw`, `normalized`, `validated`, and `errors`.
