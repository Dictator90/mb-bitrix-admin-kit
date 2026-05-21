# Пайплайн сохранения

Admin Kit v0.3.0 сохраняет данные CRUD-формы через единый пайплайн:

1. чтение сырых значений POST;
2. нормализация каждого редактируемого поля через `Field::normalize()`;
3. валидация полей через `Field::runValidation()`;
4. вызов `CrudResource::beforeValidate()` и `CrudResource::afterValidate()`;
5. сохранение через `CrudPersister`;
6. lifecycle-хуки create/update и события Bitrix;
7. показ ошибок валидации по полям или ORM при неуспешной операции.

`FormData` хранит четыре явных этапа: `raw`, `normalized`, `validated` и `errors`.
