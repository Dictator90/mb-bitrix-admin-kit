# Import/Export

## Когда использовать

Когда нужен CSV-экспорт из `IndexPage` или библиотечный CSV-импорт.

## Минимальный пример

```php
public function allowExportByFilter(): bool
{
    return true;
}

public function allowExportAll(): bool
{
    return false;
}

public function maxExportRows(): int
{
    return 5000;
}
```

## Канонические правила экспорта

- Export UI включен (CSV).
- Export by filter разрешен по умолчанию (`allowExportByFilter(): true`).
- Full export выключен по умолчанию (`allowExportAll(): false`).
- До выборки данных проверяется лимит `maxExportRows()`.

## Import (library-only)

- `ImportAction`, `ImportContext`, `CsvImporter` доступны как сервисный слой.
- Import UI на index-страницах временно отключен.
- Импорт использует `Form\DataPipeline` для общей нормализации/валидации полей.

## Ограничения

- XLSX/Excel не поддерживается в текущем scope.
- Не обещайте пользователям Import UI до отдельной задачи на UI-реинтеграцию.

## См. также

- [Reference: Actions](../reference/actions.md)
- [Cookbook: import/export](../cookbook/import-export.md)
