# Формы

Формы рендерят `formFields()`, нормализуют входящие данные запроса через объекты Field, валидируют значения, выполняют lifecycle-хуки, проверяют права и сохраняют результат.

## FormData

`FormData` учитывает стадии и хранит отдельно raw, normalized, validated и errors. Его формат стабилен для v1.x и не должен меняться в minor или patch релизах.

## Сохранение DataManagerResource (EntityObject)

`DataManagerResource` **всегда** сохраняется через Bitrix ORM EntityObject. Отдельного array persistence mode нет.

`FormPage` для ORM-ресурсов:

1. собирает поля формы;
2. разделяет скалярные поля и поля связей (`RelationField` в ORM-режиме);
3. нормализует и валидирует значения через `DataPipeline` (без `DataManager::add` / `update` для финального save);
4. загружает существующий объект через `findObject()` или создаёт через `newObject()`;
5. применяет скаляры к объекту (`$entityObject->set()`), связи — через `RelationObjectMutator`;
6. сохраняет `$entityObject->save()` и обрабатывает ошибки Bitrix `Result`.

См. [relations.md](relations.md) для режимов полей связей и ограничений.

## Сохранение CrudResource (custom/manual)

`CrudResource` — DSL и страницы без встроенной ORM persistence. Если ресурс реализует `ResourcePersistenceContract` вручную, `FormPage` использует прежний поток: `DataPipeline` → `createItemResult()` / `updateItemResult()`.

## Валидация

Используйте `normalize()` и API валидации Field для всех потоков create/edit/import. Не дублируйте правила нормализации только для импорта, если то же поведение должно быть в Field.
