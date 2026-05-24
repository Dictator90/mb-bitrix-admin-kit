# Рецепт: безопасный CSV export + сервисный import

## Задача

Разрешить экспорт по фильтру, запретить full export и использовать import только как service layer.

## Решение

```php
<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin\Resource;

final class ProductResource extends \MB\Bitrix\AdminKit\Resource\DataManagerResource
{
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
}
```

```php
<?php

use MB\Bitrix\AdminKit\Import\ImportAction;
use MB\Bitrix\AdminKit\Import\ImportContext;

$action = ImportAction::make();
$context = new ImportContext($resource);

$result = $action->import($context);
```

## Важные замечания

- export UI доступен для CSV;
- import UI в index-страницах временно отключен;
- XLSX/Excel формат не поддерживается;
- limit `maxExportRows()` защищает от тяжелых выгрузок.

## Ссылки

- [Guide: import/export](../guides/import-export.md)
- [Bulk actions](../guides/bulk-actions.md)
