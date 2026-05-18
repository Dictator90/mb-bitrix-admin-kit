# Экспорт (CSV)

> Import UI на index-страницах и тулбаре временно отключён.  
> Классы `MB\Bitrix\AdminKit\Import\*` остаются в библиотеке для будущего включения, но в текущей ветке не подключены к `IndexPage`.

## Принципы

- Экспорт CSV-first.
- XLSX/Excel-движки не входят в текущий scope.
- Экспорт должен быть безопасным по умолчанию.

## Безопасность экспорта

- Требуются выбранные ID или разрешённый экспорт по фильтру.
- Полный экспорт выключен по умолчанию (`allowExportAll(): false`).
- Проверяется `canView()` у ресурса.
- Превышение `maxExportRows()` останавливает экспорт до выборки данных.

## Основные классы

- `ExportAction` — точка входа.
- `ExportContext` — контекст операции.
- `MB\Bitrix\AdminKit\Export\CsvExporter` — CSV-экспортёр.

## Хуки ресурса

```php
public function allowExportByFilter(): bool { return true; }
public function allowExportAll(): bool { return false; }
public function maxExportRows(): int { return 5000; }
```

## Примечание по import

Для import см. [docs/import.md](import.md): там описан только библиотечный слой без UI-флоу.
