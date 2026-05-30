# Import / Export

Импорт/экспорт в `mb4it/bitrix-admin-kit` реализован как CSV-first слой сервисов поверх Resource и Form/DataPipeline.

## Текущий статус

- Экспорт по умолчанию **выключен** и включается единым флагом `exportEnabled()` на ресурсе.
- Экспорт доступен как часть action-сценариев и работает в CSV-формате.
- Import UI на `IndexPage` сейчас отключен и рассматривается как отдельная UI-задача.
- XLSX/Excel-движок в публичный API не входит.

## Когда использовать

- Когда нужен безопасный массовый обмен данными в формате CSV.
- Когда важно переиспользовать ту же нормализацию и валидацию полей, что и в form save.

## Куда идти дальше

- [Guide: Import / Export](user/guides/import-export.md)
- [Cookbook: Import / Export](user/cookbook/import-export.md)
- [Reference: Actions](user/reference/actions.md)
