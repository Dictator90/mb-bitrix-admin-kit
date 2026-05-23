# Reference: Resources

## Базовые классы

- `MB\Bitrix\AdminKit\Resource\Resource`
- `MB\Bitrix\AdminKit\Resource\CrudResource`
- `MB\Bitrix\AdminKit\Resource\DataManagerResource`

## Что стабильно в v1.x

- Сигнатуры публичных/защищенных методов
- Контракты прав (`canView/canCreate/canUpdate/canDelete`)
- API полей/фильтров/действий/страниц ресурса

## Ключевые extension points

- Fields: `indexFields()`, `formFields()`, `detailFields()`
- Filters: `filters()`
- Actions: `rowActions()`, `bulkActions()`
- Pages: `pages()`
- Grid query hooks: `indexSelect/indexFilter/indexOrder/indexRuntime/modifyIndexParams`
- Export policy: `allowExportByFilter/allowExportAll/maxExportRows`
- Performance: `useTotalCount/maxPageSize/maxBulkRows/bulkChunkSize`

## Ограничения

- `CrudResource` — DSL без ORM persistence (`hasCrud(): false`).
- `DataManagerResource` — ORM ресурс с `EntityObject` сохранением формы.
