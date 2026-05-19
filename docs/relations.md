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
- Значения связей исключаются из скалярного payload `FormPage` в режиме формы EntityObject.

Ручная синхронизация pivot доступна через `saveUsingManualSync()` и использует `ManualPivotSynchronizer`.

## Runtime-связи с явной конфигурацией

Когда поле объявляет `relatedTable()`, `foreignKey()` и опциональные метаданные pivot, `RuntimeRelationRegistrar` регистрирует поля Bitrix ORM через `RuntimeRelationBuilder`:

- `BelongsTo` → `Reference` (`this.FK = ref.ID`)
- `HasOne` → обратный `Reference` (`this.ID = ref.FK`)
- `HasMany` → `OneToMany`
- `BelongsToMany` → `ManyToMany` (требуются явные pivot keys)

## Режим формы EntityObject (opt-in)

Включение на `DataManagerResource`:

```php
final class ProductResource extends DataManagerResource
{
    public function __construct()
    {
        $this->enableEntityObjectForm(true);
    }
}
```

`FormPage` затем:

1. разделяет скалярные поля и поля связей;
2. валидирует скаляры через `DataPipeline`;
3. загружает/создаёт `EntityObject` через `findObject()` / `createObject()`;
4. синхронизирует связи через `OrmObjectRelationSynchronizer` / `RelationObjectMutator`;
5. сохраняет через `$entityObject->save()`.

Режим массива остаётся по умолчанию (`usesEntityObjectForm()` → `false`).

## Режимы отображения

| Поле | Режим | Поддержка в форме |
|------|-------|-------------------|
| BelongsTo | `asSelect()` | да (по умолчанию) |
| BelongsTo | `asRadio()` | да |
| BelongsTo | `asLink()` | превью/только чтение в форме |
| BelongsTo | `asEntitySelector()` | не настроен (показывается предупреждение) |
| HasOne | `asPreview()` | только чтение |
| HasOne | `asEmbeddedForm()` | только вложенное превью |
| HasMany | `asTable()` | превью таблицы, только чтение |
| HasMany | `asEmbeddedForm()` / `asGrid()` | ограничено; задокументировано |

## Текущие ограничения

- Runtime `ManyToMany` требует явных `foreignPivotKey()` и `relatedPivotKey()`, если ключи посредника нельзя разрешить из метаданных ORM.
- Удаление/синхронизация `HasMany` требует явного `orphanRemoval()` или `cascadeDelete()`; иначе только update/create без тихих удалений.
- Вложенное сохранение `HasOne` поддерживает payload-массивы только при настроенном `relatedEntity`.
- Извлечение ключей из метаданных ORM зависит от публичных API Bitrix ORM; неразрешённые ключи остаются `null`.
