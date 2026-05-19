# Формы

Формы рендерят `formFields()`, нормализуют входящие данные запроса через объекты Field, валидируют значения, выполняют lifecycle-хуки, проверяют права и сохраняют через `CrudPersister`.

## FormData

`FormData` учитывает стадии и хранит отдельно raw, normalized, validated и errors. Его формат стабилен для v1.x и не должен меняться в minor или patch релизах.

## Режим формы EntityObject

По умолчанию `FormPage` сохраняет скалярные массивы через `updateItemResult()` / `createItemResult()`.

Подключение на ресурсе:

```php
$this->enableEntityObjectForm(true);
```

Тогда `FormPage` использует `EntityObjectFormSaver`: скалярные поля проходят через `DataPipeline`, поля связей синхронизируются через `RelationObjectMutator`, владелец сохраняется через Bitrix `$entityObject->save()`.

См. [relations.md](relations.md) для режимов полей связей и ограничений.

## Валидация и сохранение

Используйте `normalize()` и API валидации Field для всех потоков create/edit/import. Не дублируйте правила нормализации только для импорта, если то же поведение должно быть в Field.
