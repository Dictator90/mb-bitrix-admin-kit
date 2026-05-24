# MB Bitrix Admin Kit

`mb4it/bitrix-admin-kit` — Bitrix-first пакет для построения административных CRUD-разделов на базе D7 ORM и нативных Bitrix UI-компонентов (`main.ui.grid`, `main.ui.filter`, SidePanel).

## Требования

- PHP `^8.2`
- 1C-Битрикс с D7 ORM
- Composer

## Установка

```bash
composer require mb4it/bitrix-admin-kit
```

Подробные шаги подключения: [Documentation → Installation](docs/installation.md).

## Минимальный Resource (ORM CRUD)

```php
<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use Vendor\Demo\Orm\ProductTable;

final class ProductResource extends DataManagerResource
{
    protected string $title = 'Товары';

    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function indexFields(): iterable
    {
        return [
            ID::make('ID', 'ID'),
            Text::make('Название', 'NAME'),
            Switcher::make('Активен', 'ACTIVE'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            Text::make('Название', 'NAME')->required(),
            Switcher::make('Активен', 'ACTIVE')->default(true),
        ];
    }

    public function filters(): iterable
    {
        return [
            TextFilter::make('Название', 'NAME')->contains(),
        ];
    }

    public function rowActions(): iterable
    {
        return [
            RowAction::view(),
            RowAction::edit(),
            RowAction::delete(),
        ];
    }

    public function bulkActions(): iterable
    {
        return [
            BulkAction::delete(),
        ];
    }
}
```

## Ключевые возможности

- CRUD для D7 ORM через `DataManagerResource`.
- Grid на `bitrix:main.ui.grid` и фильтры на `main.ui.filter`.
- RowAction / BulkAction, включая безопасные массовые операции.
- Standalone-страницы: `OptionsPage`, `DashboardPage`, `CustomPage`.
- Bitrix-native подход: SidePanel, toolbar, UI extensions.

## Документация

- [Documentation map](docs/README.md)
- [Installation](docs/installation.md)
- [Quick Start](docs/quick-start.md)
- [Resources](docs/resources.md)
- [Fields](docs/fields.md)
- [Grid](docs/grid.md)
- [Actions](docs/actions.md)
- [Bulk actions](docs/bulk-actions.md)
- [OptionsPage](docs/options-page.md)
- [DashboardPage](docs/dashboard-page.md)
- [Import/Export](docs/user/guides/import-export.md)
