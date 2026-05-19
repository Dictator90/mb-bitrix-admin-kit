# Экспорт ресурса

В v0.7.0 добавлен CSV-first слой экспорта для CRUD-ресурсов.

## Компоненты

- `ExportAction` проверяет права и решает, можно ли экспортировать выбранные строки, строки по фильтру или все строки.
- `ExportContext` передаёт resource, selected IDs, filter, поля экспорта, user ID, format и опциональный `GridContext`.
- `CsvExporter` пишет UTF-8 CSV с BOM по умолчанию и использует безопасное экранирование `fputcsv()`.

## Экспорт выбранных

Передайте явные selected IDs в `ExportContext`. Действие добавляет фильтр по первичному ключу и экспортирует только эти строки.

```php
$result = ExportAction::make()->execute(new ExportContext(
    resource: $resource,
    selectedIds: [1, 2, 3],
));
```

## Экспорт по фильтру

Передайте текущий filter и при наличии `GridContext`. Экспорт по фильтру включён по умолчанию, но resource может отключить его через `allowExportByFilter(): bool`.

```php
$result = ExportAction::make()->execute(new ExportContext(
    resource: $resource,
    filter: ['ACTIVE' => 'Y'],
    gridContext: $context,
));
```

## Полный экспорт

Экспорт всех записей по умолчанию заблокирован. Явно включите на действии через `allowRunAll()` или на resource через `allowExportAll(): bool`.

## Поля и права

- Resource должен проходить `canView()` для экспорта.
- Условие `canRun()` действия должно выполняться.
- `CsvExporter` использует `indexFields()`, если поля не переданы в context.
- Поля, скрытые с индекса, с `exportable(false)`, `private()` или `system()`, не экспортируются.
- Вычисляемые поля и `displayUsing()` учитываются в CSV-выводе.
