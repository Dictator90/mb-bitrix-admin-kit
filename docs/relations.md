# Связи (Relations)

Поля связей находятся только в пространстве имён `MB\Bitrix\AdminKit\Field\Relation`. Устаревшее пространство имён `MB\Bitrix\AdminKit\Field\BelongsTo` и аналогичные классы не поддерживаются.

## Типы полей

- `BelongsTo` — одна связанная сущность через FK или ORM `Reference`.
- `HasOne` — одна связанная сущность; редактирование в форме ограничено превью/встроенным превью, если не включён режим object graph.
- `HasMany` — коллекционная связь; поддерживается превью таблицей, встроенное/грид-редактирование ограничено.
- `BelongsToMany` — связь многие-ко-многим с двумя режимами хранения (см. ниже).

## Режимы BelongsToMany

### Устаревший CSV-режим (по умолчанию)

Если метаданные ORM-связи не настроены, значения хранятся в скалярной колонке как ID через запятую:

```php
BelongsToMany::make('Tags', 'TAG_IDS', TagTable::class);
```

- `isStoredAsCsv()` → `true`
- `serializePostValue(['1','2'])` → `"1,2"`

### Режим ORM-связи

Включается при настройке любого из следующих параметров:

- `relation('TAGS')`
- `relatedTable()` / `pivotTable()`
- `saveUsingOrm()`
- `saveUsingManualSync()`
- `storedAsCsv(false)`

```php
BelongsToMany::make('Tags', 'TAGS')
    ->relation('TAGS')
    ->relatedTable(TagTable::class)
    ->pivotTable(ProductTagTable::class)
    ->foreignPivotKey('PRODUCT_ID')
    ->relatedPivotKey('TAG_ID');
```

- `isOrmRelationMode()` → `true`
- `serializePostValue(['1','2'])` → `['1','2']`
- Значения связей исключаются из скалярного payload `FormPage` для `DataManagerResource`.

Ручная синхронизация pivot доступна через `saveUsingManualSync()` и использует `ManualPivotSynchronizer`.

### Pivot без Reference-полей (manual only)

Таблицы вроде `EventMessageSiteTable` содержат только колонки связи (`EVENT_MESSAGE_ID`, `SITE_ID`) **без** ORM `Reference` на mediator.
Для них **не** вызывайте `mediatorReferences()` и **не** ожидайте runtime `ManyToMany` — AdminKit читает/пишет pivot напрямую:

```php
BelongsToMany::make('Сайты', 'SITES')
    ->relatedTable(SiteTable::class) // или список LID через options()
    ->pivotTable(EventMessageSiteTable::class)
    ->foreignPivotKey('EVENT_MESSAGE_ID')
    ->relatedPivotKey('SITE_ID')
    ->saveUsingManualSync(); // или saveUsingOrm() + pivot keys — sync через ManualPivotSynchronizer
```

Runtime `ManyToMany` регистрируется **только** если заданы `mediatorReferences('LOCAL_REF', 'REMOTE_REF')` — имена Reference на pivot-сущности (как `IBLOCK_ELEMENT` / `IBLOCK_SECTION` у `SectionElementTable`).

Имена scalar-колонок pivot (`USER_ID`, `GROUP_ID` и т.п.) можно не дублировать в DSL: при наличии `mediatorReferences()` AdminKit выводит их из Reference-полей mediator-сущности и сохраняет связь через `ManualPivotSynchronizer` (а не через `EntityObject::set()` на runtime ManyToMany).

Порядок в `mediatorReferences($local, $remote)`: **local** — Reference на mediator к владельцу формы (owner), **remote** — к связанной сущности. Для `UserTable` → `GroupTable` это `('USER', 'GROUP')`; для `GroupTable` → `UserTable` — `('GROUP', 'USER')`. Если порядок указан наоборот, AdminKit переупорядочит ссылки по `ownerEntity` / `relatedEntity` перед регистрацией runtime `ManyToMany`.

## Runtime-связи с явной конфигурацией

Когда поле объявляет `relatedTable()`, `foreignKey()` и опциональные метаданные pivot, `RuntimeRelationRegistrar` регистрирует поля Bitrix ORM через `RuntimeRelationBuilder`:

- `BelongsTo` → `Reference` (`this.FK = ref.ID`)
- `HasOne` → обратный `Reference` (`this.ID = ref.FK`)
- `HasMany` → `OneToMany`
- `BelongsToMany` → `ManyToMany` (требуются `mediatorReferences()` с именами Reference на pivot)

## Сохранение через EntityObject (DataManagerResource)

Для `DataManagerResource` `FormPage` **всегда** использует object-graph flow:

1. разделяет скалярные поля и поля связей;
2. валидирует скаляры и связи через `DataPipeline`;
3. регистрирует runtime-связи (explicit config) до `findObject()`;
4. загружает/создаёт объект через `findObject()` / `newObject()`;
5. синхронизирует связи через `OrmObjectRelationSynchronizer` / `RelationObjectMutator`;
6. сохраняет через `$entityObject->save()`.

`CrudResource` с ручной persistence по-прежнему использует `createItemResult()` / `updateItemResult()`.

## Режимы отображения

| Поле | Режим | Поддержка в форме |
|------|-------|-------------------|
| BelongsTo | `asSelect()` | да (по умолчанию) |
| BelongsTo | `asRadio()` | да |
| BelongsTo | `asLink()` | превью/только чтение в форме |
| BelongsTo | `asEntitySelector()` | не настроен (показывается предупреждение) |
| HasOne | `asPreview()` | только чтение |
| HasOne | `asEmbeddedForm()` | только вложенное превью |
| HasMany | `asTable($columns)` | превью таблицы, только чтение; `$columns` — список полей или map `поле => подпись` (без подписи — `getTitle()` из related ORM) |
| HasMany | `asEmbeddedForm()` / `asGrid()` | ограничено; задокументировано |

## Текущие ограничения

- Runtime `ManyToMany` требует явных `foreignPivotKey()` и `relatedPivotKey()`, если ключи посредника нельзя разрешить из метаданных ORM.
- Удаление/синхронизация `HasMany` требует явного `orphanRemoval()` или `cascadeDelete()`; иначе только update/create без тихих удалений.
- Вложенное сохранение `HasOne` поддерживает payload-массивы только при настроенном `relatedEntity`.
- Извлечение ключей из метаданных ORM зависит от публичных API Bitrix ORM; неразрешённые ключи остаются `null`.
