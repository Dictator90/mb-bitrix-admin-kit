# Cookbook

Практические рецепты по принципу «как сделать X».

## CRUD

- [First CRUD Resource](first-crud.md) — поднять первый CRUD-раздел на базе `DataManagerResource`.
- [Add Grid column](add-grid-column.md) — добавить и настроить колонку на index-странице.
- [Add filter](add-filter.md) — подключить фильтры `main.ui.filter` и связать их с ORM-фильтром.

## Actions

- [Add RowAction](add-row-action.md) — добавить стандартные и кастомные действия строки.
- [Add BulkAction](add-bulk-action.md) — безопасно запустить массовые операции по selected ID или фильтру.
- [Use SidePanel](sidepanel.md) — открывать form/detail/actions в Bitrix SidePanel.

## Pages

- [OptionsPage](options-page.md) — сделать страницу настроек модуля через `b_option`.
- [DashboardPage](dashboard.md) — собрать dashboard со счетчиками/графиками на Bitrix UI.

## Fields

- [Custom Field](custom-field.md) — расширить базовый `Field` для собственного UI/нормализации.
- [Relation Field](relation-field.md) — использовать `BelongsTo/HasOne/HasMany/BelongsToMany` в CRUD.
- [Computed column](computed-column.md) — показать вычисляемое значение в grid без изменения ORM-схемы.

## Import / Export

- [Import / Export](import-export.md) — CSV export из index и сервисный CSV import через pipeline.

## Security

- [Permissions](permissions.md) — разграничить доступ на уровне ресурса и действий.
- [Add BulkAction](add-bulk-action.md) — safe-by-default массовые операции и all-records режим.

Полный API смотрите в [Reference](../reference/resources.md).
