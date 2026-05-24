# Import / Export

## Задача

Экспортировать записи в CSV и выполнять сервисный импорт через общий form pipeline.

## Когда использовать

Для обмена данными с внешними системами и пакетной загрузки.

## Решение

Export запускается из index: по selected ID или по фильтру (если action это разрешает). Полный экспорт без фильтра должен оставаться отключенным по умолчанию.

Import UI в index сейчас отключен; используйте `Import\*` как сервисный слой (CLI/cron/кастомная админ-форма).

## Полный пример

```php
use MB\Bitrix\AdminKit\Export\CsvExporter;
use MB\Bitrix\AdminKit\Import\CsvImporter;
use MB\Bitrix\AdminKit\Import\ImportContext;

$exporter = new CsvExporter();
$content = $exporter->export($resource, $rows);

$importer = new CsvImporter();
$result = $importer->import($resource, $csvString, new ImportContext());
```

## Как это работает

Import повторно использует `Form\DataPipeline`, поэтому `normalize()` и валидация полей одинаковы для формы и CSV.

## Что важно учесть

- Поддерживается CSV-first подход; XLSX/Excel движок не заявлен как стандартный.
- Учитывайте `exportable/importable/private/system` поведение полей.
- Для массовых операций обязательно проверяйте права, CSRF и лимиты выполнения.

## Связанные разделы

- [Import / Export](../../import-export.md)
- [Guides: Import / Export](../guides/import-export.md)
