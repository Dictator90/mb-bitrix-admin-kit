# Import / Export

## Что это

CSV-first экспорт и сервисный импорт для `DataManagerResource`.

## Текущий статус

| Возможность | Статус |
|---|---|
| Экспорт в целом | **По умолчанию выключен** (`exportEnabled()`) |
| CSV export UI | Да (когда экспорт включён) |
| Export selected | Да (когда экспорт включён) |
| Export by filter | Да (когда экспорт включён) |
| Full export | По умолчанию выключен (`allowExportAll()`) |
| CSV import service layer | Да |
| Import UI | Временно отключен на IndexPage |
| XLSX | Не поддерживается |

## Export

### Главный выключатель

Экспорт целиком управляется одним методом `exportEnabled()` (default `false`).
Он действует везде: кнопка экспорта в тулбаре, действие «Экспорт выбранных» в групповой панели
и эндпоинты `action=export`/`export_selected` (прямой URL при выключенном экспорте редиректит на список).

```php
<?php

public function exportEnabled(): bool
{
    return true; // по умолчанию экспорт выключен
}
```

### Базовый пример

```php
<?php

public function exportEnabled(): bool
{
    return true;
}

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

- экспорт выключен по умолчанию — включается явно через `exportEnabled()`;
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
