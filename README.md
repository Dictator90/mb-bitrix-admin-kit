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
- `CrudResource` — DSL/страничная база без persistence; для рабочего ORM CRUD используйте `DataManagerResource`.
- Bulk AJAX возвращает структурированный результат (`status`, `message`, `errors`, `warnings`, `affected`) и показывает ошибки сразу через `mb.admin.kit`.
- Поля связей находятся только в `MB\Bitrix\AdminKit\Field\Relation` (старый namespace `Field\BelongsTo` и т.п. не поддерживается).
- `DataManagerResource` всегда сохраняет формы через Bitrix EntityObject (`findObject()` / `newObject()` / `$entityObject->save()`). Array persistence mode для ORM-ресурсов не поддерживается. Подробнее: [docs/relations.md](docs/relations.md), [docs/forms.md](docs/forms.md).

## Требования

- PHP `^8.2`
- 1С‑Битрикс с D7 ORM
- Composer

## Установка

```bash
composer require mb4it/bitrix-admin-kit
```

AdminKit можно подключать как внутри Bitrix-модуля, так и вне модуля.

### Внутри Bitrix-модуля

Пакет можно установить в директорию модуля или в корень проекта. В `include.php` модуля подключите module `vendor/autoload.php` с fallback на проектный `vendor/autoload.php`, затем в admin-файле явно подключите модуль и создайте scope по module id:

```php
use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\Manager\AdminKitManager;
use MB\Bitrix\AdminKit\Manager\AdminKitScope;

Loader::includeModule('vendor.demo');

$scope = AdminKitScope::fromModuleId('vendor.demo');
(new AdminKitManager($scope))->getCurrentPage()->render();
```

`Loader::includeModule()` подключает модуль и его классы, а `AdminKitScope::fromModuleId()` отвечает за discovery `Resource`/`Page` классов, по умолчанию из `lib/Admin`.

### Вне модуля

Установите пакет в корне проекта, подключите `vendor/autoload.php` в `local/php_interface/init.php` или конкретном admin-файле, а scope создайте по абсолютному пути к локальным классам:

```php
use MB\Bitrix\AdminKit\Manager\AdminKitManager;
use MB\Bitrix\AdminKit\Manager\AdminKitScope;

$scope = AdminKitScope::fromDirectory(
    $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
    'local.admin'
);

(new AdminKitManager($scope))->getCurrentPage()->render();
```

Подробнее: [docs/installation.md](docs/installation.md), [docs/quick-start.md](docs/quick-start.md), [docs/discovery.md](docs/discovery.md).

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
- Scope и discovery: [docs/discovery.md](docs/discovery.md)
- Ресурсы и страницы: [docs/resources.md](docs/resources.md), [docs/pages.md](docs/pages.md)
- Грид: [docs/grid.md](docs/grid.md)
- Поля и фильтры: [docs/fields.md](docs/fields.md), [docs/filters.md](docs/filters.md), [docs/relations.md](docs/relations.md)
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
