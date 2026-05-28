# Import / Export

## Что это

CSV-first экспорт и сервисный импорт для `DataManagerResource`.

## Текущий статус

| Возможность | Статус |
|---|---|
| CSV export UI | Да |
| Export selected | Да |
| Export by filter | Да (по умолчанию) |
| Full export | По умолчанию выключен |
| CSV import service layer | Да |
| Import UI | Временно отключен на IndexPage |
| XLSX | Не поддерживается |

## Export

### Базовый пример

```php
<?php

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

### Политика безопасности

- нужен `canView(..., 'export')`;
- full export блокируется без selected IDs/filter, пока не включен opt-in (`allowRunAll()` или `resource->allowExportAll()`);
- export by filter можно запретить action-level или resource-level;
- лимит строк контролируется `maxExportRows()`.

## Import

### Текущий статус

- `ImportAction`, `ImportContext`, `CsvImporter` доступны как service layer;
- Import UI в index-страницах временно отключен;
- pipeline импорта использует нормализацию/валидацию полей.

### Service layer example

```php
<?php

use MB\Bitrix\AdminKit\Import\ImportAction;
use MB\Bitrix\AdminKit\Import\ImportContext;

$action = ImportAction::make();
$context = new ImportContext($resource);

$result = $action->import($context);
```

## Практические сценарии

- экспорт выбранных записей;
- экспорт по фильтру;
- запрет full export по умолчанию;
- библиотечный CSV импорт без UI-экрана.

## Связанные разделы

- [Actions reference](../reference/actions.md)
- [Bulk actions guide](bulk-actions.md)
- [Cookbook: import/export](../cookbook/import-export.md)
