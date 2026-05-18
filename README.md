# MB Bitrix Admin Kit

`mb4it/bitrix-admin-kit` — пакет для декларативной сборки административных CRUD-разделов 1С‑Битрикс на D7 ORM.

## Что умеет пакет

- Описывать `Resource`, `Field`, `Filter`, `Action` в PHP-классах.
- Рендерить index/form/detail страницы в админке.
- Поддерживать безопасные bulk-операции с проверками CSRF, прав и лимитов.
- Работать со standalone-страницами: `Pages\OptionsPage`, `Pages\DashboardPage`, `Page\Standalone\CustomPage`.
- Экспортировать данные в CSV через `ExportAction`.

## Важные ограничения текущей ветки

- Import UI на index-страницах временно отключён.
- Экспорт остаётся CSV-first (`MB\Bitrix\AdminKit\Export\CsvExporter`).
- Для новых ORM-ресурсов рекомендуется `DataManagerResource`.

## Требования

- PHP `^8.2`
- 1С‑Битрикс с D7 ORM
- Composer

## Установка

```bash
composer require mb4it/bitrix-admin-kit
```

В `include.php` модуля подключите autoload:

```php
<?php

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
```

## Быстрый пример ресурса

```php
<?php

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
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
            ID::make('ID'),
            Text::make('Название', 'NAME'),
        ];
    }
}
```

## Навигация по документации

- Установка: [docs/installation.md](docs/installation.md)
- Быстрый старт: [docs/quick-start.md](docs/quick-start.md)
- Архитектура: [docs/architecture.md](docs/architecture.md)
- Ресурсы и страницы: [docs/resources.md](docs/resources.md), [docs/pages.md](docs/pages.md)
- Грид: [docs/grid.md](docs/grid.md)
- Поля и фильтры: [docs/fields.md](docs/fields.md), [docs/filters.md](docs/filters.md)
- Действия: [docs/actions.md](docs/actions.md), [docs/bulk-actions.md](docs/bulk-actions.md)
- Экспорт/импорт: [docs/import-export.md](docs/import-export.md), [docs/import.md](docs/import.md)
- Совместимость: [docs/backward-compatibility.md](docs/backward-compatibility.md)
- Миграции и обновления: [docs/upgrade.md](docs/upgrade.md)
- Рецепты: [docs/cookbook/README.md](docs/cookbook/README.md)

## Разработка пакета

```bash
composer test
composer analyse
composer cs-check
```
