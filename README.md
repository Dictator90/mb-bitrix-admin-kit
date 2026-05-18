# MB Bitrix Admin Kit

`mb4it/bitrix-admin-kit` — декларативный набор классов для админских CRUD-разделов 1С-Битрикс на Bitrix D7 ORM. Пакет помогает описать Resource, Field, Filter, Action, OptionsPage, DashboardPage и CustomPage в PHP-классах модуля, а затем вывести список, форму, меню, CSV-экспорт и SidePanel без копирования типового admin-кода.

## Для каких задач нужен пакет

- Быстро создать CRUD для ORM `DataManager` в админке модуля.
- Описывать поля формы и колонки грида в одном Resource.
- Добавлять фильтры `main.ui.filter`, row actions и безопасные bulk actions.
- Открывать create/edit/detail формы в `BX.SidePanel`.
- Делать страницы настроек модуля и произвольные dashboard/report страницы.
- Кастомизировать ORM-запросы: select, filter, order, runtime fields, computed columns.
- Экспортировать данные в CSV (без XLSX) с лимитами и проверкой прав.
- Строить страницы настроек (`Pages\OptionsPage`) и dashboard-страницы (`Pages\DashboardPage`) отдельно от ORM CRUD.

Пакет не заменяет Bitrix D7 ORM и не скрывает его полностью: бизнес-специфичные связи, `ReferenceField` и сложные фильтры остаются на стороне Resource.

## Требования

- PHP 8.2+.
- 1С-Битрикс с D7 ORM и административной частью.
- Composer в модуле или проекте.
- Для разработки пакета: PHPUnit, PHPStan и php-cs-fixer из `require-dev`.

## Установка

```bash
composer require mb4it/bitrix-admin-kit
```

Если пакет ставится внутри Bitrix-модуля, подключайте Composer autoload в `include.php` модуля:

```php
<?php

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
```

## Public facade and discovery

AdminKit is module-first, but not module-only. Each manager is created for a `scopeId` — a unique AdminKit area identifier. In a Bitrix module this usually matches the module ID, for example `vendor.demo`; for project-level admin tools it can be `site.admin`, `catalog.admin`, or any other stable string.

### Bitrix module

```php
use MB\Bitrix\AdminKit\AdminKit;

$adminKit = AdminKit::forModule('vendor.demo')
    ->discoverIn('/local/modules/vendor.demo/lib');
```

### Module object

```php
$adminKit = AdminKit::forModule($moduleObject);
```

If the object exposes `getModuleId()`, `getId()`, `id()`, public `moduleId`, or public `id`, AdminKit uses that value as the scope ID. If the object exposes `getLibPath()` or public `libPath`, that path is added to discovery automatically.

### `local/php_interface` resources

```php
$adminKit = AdminKit::forScope('site.admin')
    ->discoverIn('/local/php_interface/lib/Admin');
```

### Directory shortcut

```php
$adminKit = AdminKit::fromDirectory(
    '/local/php_interface/lib/Admin',
    scopeId: 'site.admin'
);
```

Multiple directories can be discovered for one scope:

```php
$adminKit = AdminKit::fromDirectories([
    '/local/php_interface/lib/Admin',
    '/local/php_interface/lib/Tools',
], scopeId: 'site.admin');
```

### Manual registration without discovery

```php
$adminKit = AdminKit::forScope('site.admin')
    ->register(ProductResource::class)
    ->registerPage(SettingsPage::class);
```

The manager remains the primary facade for resource/page registration, menu building, routing, and rendering. See `docs/discovery.md` for all discovery options and missing-path behavior.

## Подключение в Bitrix-модуле

Минимальная структура модуля:

```text
local/modules/vendor.demo/
├── include.php
├── admin/demo_admin.php
├── admin/menu.php
└── lib/Admin/ProductResource.php
```

`admin/demo_admin.php` должен подключить прологи Bitrix и отдать рендеринг менеджеру или конкретному Resource:

```php
<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/vendor.demo/include.php';

use Vendor\Demo\Admin\ProductResource;

$resource = new ProductResource();
$action = (string)($_REQUEST['action'] ?? 'index');
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : null;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

match ($action) {
    'add' => $resource->formPage()->render(),
    'edit' => $resource->formPage($id)->render(),
    'detail' => $resource->detailPage($id)->render(),
    default => $resource->indexPage()->render(),
};

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

`admin/menu.php` возвращает стандартный массив меню Bitrix:

```php
<?php

use Vendor\Demo\Admin\ProductResource;

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/vendor.demo/include.php';

return [[
    'parent_menu' => 'global_menu_content',
    'section' => ProductResource::getId(),
    'sort' => ProductResource::getSort(),
    'text' => 'Demo products',
    'title' => 'Demo products',
    'url' => 'demo_admin.php?page=' . ProductResource::getId(),
    'icon' => ProductResource::getMenuIcon(),
]];
```

Полный пример находится в `examples/demo-module`.

## Первый Resource

Новые ORM CRUD-разделы наследуйте от `CrudResource`. `Resource` остаётся совместимой базой для существующих классов. Настройки модуля оформляйте через `Pages\OptionsPage`.

```php
<?php

namespace Vendor\Demo\Admin;

use Vendor\Demo\Orm\ProductTable;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Filter\Types\SelectFilter;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;

final class ProductResource extends DataManagerResource
{
    protected string $title = 'Products';

    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function indexFields(): iterable
    {
        return [
            ID::make('ID'),
            Text::make('Name', 'NAME'),
            Select::make('Type', 'TYPE')->options(['simple' => 'Simple', 'service' => 'Service']),
            Switcher::make('Active', 'ACTIVE')->values('Y', 'N'),
        ];
    }

    public function formFields(): iterable
    {
        return [
            Text::make('Name', 'NAME')->required(),
            Select::make('Type', 'TYPE')->options(['simple' => 'Simple', 'service' => 'Service'])->default('simple'),
            Switcher::make('Active', 'ACTIVE')->values('Y', 'N')->default('Y'),
        ];
    }

    public function filters(): iterable
    {
        return [
            TextFilter::make('Name', 'NAME'),
            SelectFilter::make('Type', 'TYPE')->options(['simple' => 'Simple', 'service' => 'Service']),
        ];
    }
}
```

## Как вывести CRUD

Resource наследует методы `indexPage()`, `formPage($id = null)` и `detailPage($id)` из CRUD-слоя. В admin-файле выберите страницу по `action` и вызовите `render()`. Для нового раздела чаще всего достаточно `index`, `add`, `edit`, `detail` и `delete`.

## Resource::pages() и кастомные CRUD-страницы

По умолчанию Resource регистрирует `IndexPage`, `FormPage` и `DetailPage`. Для кастомизации UI переопределите `pages()`:

```php
public function pages(): iterable
{
    return [
        ProductIndexPage::class,
        ProductFormPage::class,
        ProductDetailPage::class,
    ];
}
```

`indexFields()`, `formFields()` и `detailFields()` остаются shortcuts для простых ресурсов. Кастомные page-классы получают поля через `fields()`/`tabs()` на уровне страницы. Подробности — в `docs/pages.md`.

## Поля

Field описывает колонку грида, поле формы, нормализацию и валидацию:

```php
Text::make('Name', 'NAME')->required()->placeholder('Product name');
Select::make('Type', 'TYPE')->options(['simple' => 'Simple'])->default('simple');
Switcher::make('Active', 'ACTIVE')->values('Y', 'N')->default('Y');
```

Используйте существующие Field-классы (`Text`, `Number`, `Select`, `Switcher`, `Date`, `EntitySelectorField`, `UserSelectorField` и др.) и расширяйте их при необходимости.

## Фильтры

```php
TextFilter::make('Name', 'NAME')->contains();
SelectFilter::make('Active', 'ACTIVE')->options(['Y' => 'Yes', 'N' => 'No'])->exact();
```

Фильтры пропускают пустые значения, но сохраняют значимые `0`, `'0'` и `false`.

## Actions

Row actions отображаются в меню строки:

```php
public function rowActions(): iterable
{
    return [
        RowAction::edit(),
        RowAction::view(),
        RowAction::delete(),
    ];
}
```

Bulk actions безопасны по умолчанию и требуют выбранные ID, если действие явно не разрешает запуск по фильтру. `BulkAction::delete()` сразу создаёт `MassDeleteAction`, который проверяет права на каждую запись:

```php
public function bulkActions(): iterable
{
    return [
        BulkAction::make('activate', 'Activate')
            ->group('status', 'Status')
            ->icon('ui-btn-icon-success')
            ->update(['ACTIVE' => 'Y']),
        BulkAction::delete(),
    ];
}
```

Для компактной панели можно использовать dropdown. Из-за поведения Bitrix `Types::DROPDOWN` видимый label берётся из первого элемента, поэтому AdminKit автоматически добавляет placeholder первым item; label dropdown используется как placeholder, а изменить его можно через `placeholder()`:

```php
BulkActionDropdown::make('activity', 'Активность')
    ->placeholder('Выберите действие')
    ->items([
        BulkAction::make('activate', 'Активировать')->allowRunByFilter()->update(['ACTIVE' => 'Y']),
        BulkAction::make('deactivate', 'Деактивировать')->allowRunByFilter()->update(['ACTIVE' => 'N']),
    ]);
```

Нижний checkbox "для всех" (`SHOW_SELECT_ALL_RECORDS_CHECKBOX`) появляется при наличии `allowRunByFilter()`. Он отправляет `action_all_rows_<GRID_ID>=Y`; backend в этом режиме игнорирует выбранные ID и работает по фильтру. Пустой фильтр запрещён, если действие явно не вызвало `allowRunWithoutFilter()`, а количество строк ограничено `maxBulkRows()`.

## OptionsPage (настройки модуля)

Standalone-страница для `b_option` / `b_option_site`. Не наследуйте ORM `CrudResource` для настроек — используйте `Pages\OptionsPage`. Deprecated-обёртка `Page\OptionsPage` сохранена для обратной совместимости.

```php
final class SettingsPage extends \MB\Bitrix\AdminKit\Page\Standalone\OptionsPage
{
    protected string $moduleId = 'vendor.demo';

    public static function title(): string
    {
        return 'Demo settings';
    }

    public function fields(): iterable
    {
        return [
            Text::make('API token', 'api_token')->private(),
            Switcher::make('Enabled', 'enabled')->values('Y', 'N'),
        ];
    }
}
```

## DashboardPage и CustomPage

- `Pages\DashboardPage` — dashboard/report-страницы с виджетами (`CountWidget`, `ChartWidget`; `GraphWidget` как alias), layout-компонентами и `DashboardRenderer`.
- `Pages\CustomPage` — произвольный HTML-контент через `content()`.

Обе страницы регистрируются как standalone (`registerPage()` / discovery) и не смешиваются с resource pages (`IndexPage`/`FormPage`/`DetailPage` не попадают в меню как отдельные пункты сами по себе).

## SidePanel

Включите SidePanel на Resource:

```php
public function useSidePanel(): bool
{
    return true;
}

public function sidePanelWidth(): int
{
    return 960;
}
```

`RowAction::edit()` и URL create/edit будут открываться через `BX.SidePanel`, но full-page режим сохранится, если `IFRAME=Y` отсутствует.

## Безопасность (POST, permissions, export)

- Все POST-действия на страницах требуют валидный `check_bitrix_sessid()`; при невалидной сессии сохранение не выполняется (AJAX → JSON-ошибка, обычный POST → alert).
- `IndexPage` проверяет `canView` перед grid/export, `canDelete`/`canUpdate` для inline/bulk, CSRF для bulk/inline/delete.
- `FormPage` проверяет `canView`/`canCreate`/`canUpdate` перед render/save; async save возвращает JSON.
- `DetailPage` проверяет `canView` перед отображением записи.
- CSV export (`ExportAction`) требует `canView` и лимит `maxExportRows()` (по умолчанию 5000).

```php
public function canCreate(?PermissionContext $context = null): bool
{
    return $context?->userId() === 1;
}
```

Проверяйте `canUpdate`/`canDelete` на каждую запись в bulk operations: пакет пропускает запрещенные строки, не останавливая весь batch.

## Импорт (временно отключён)

UI и flow импорта на `IndexPage` временно удалены. Классы `MB\Bitrix\AdminKit\Import\*` остаются в кодовой базе для будущего включения, но примеры с `ImportAction` / `ImportContext` / `CsvImporter` в документации не актуальны для текущей ветки.

## Кастомизация ORM-запроса

Основная точка расширения — `modifyIndexParams(array $params, GridContext $context): array`:

```php
public function modifyIndexParams(array $params, GridContext $context): array
{
    $params['filter']['=ACTIVE'] = 'Y';
    $params['select'][] = 'CATEGORY_NAME';

    return $params;
}
```

Для стабильного порядка используйте также `defaultSort()`, `defaultFilter()`, `indexSelect()`, `indexFilter()`, `indexOrder()` и `indexRuntime()`.

## Runtime fields

Передавайте Bitrix runtime-объекты в `indexRuntime()` или `modifyIndexParams()`:

```php
public function indexRuntime(GridContext $context): array
{
    return [
        new \Bitrix\Main\ORM\Fields\Relations\Reference(
            'CATEGORY',
            CategoryTable::class,
            ['=this.CATEGORY_ID' => 'ref.ID']
        ),
    ];
}
```

AdminKit не встраивает бизнес-join в grid layer: Resource сам решает, какие runtime поля нужны.

## Computed columns

Computed column не выбирается из ORM автоматически и считается на PHP-строке:

```php
Text::make('Status label', 'STATUS_LABEL')
    ->computed(static fn(array $row): string => $row['ACTIVE'] === 'Y' ? 'Active' : 'Inactive');
```

## CSV export

Экспорт CSV-first через `ExportAction` / `ExportContext` / `MB\Bitrix\AdminKit\Export\CsvExporter`:

```php
public function allowExportByFilter(): bool { return true; }
public function allowExportAll(): bool { return false; }
public function maxExportRows(): int { return 5000; }
```

Экспорт требует выбранные ID или разрешённый фильтр; полный export all выключен, пока Resource не включит `allowExportAll()`. Перед `getList()` выполняется pre-flight count по лимиту `maxExportRows()`.

## Support-пакеты

AdminKit использует `mb4it/collections`, `mb4it/stringable`, `mb4it/conditionable` и `mb4it/filesystem` через адаптеры/сервисы:

- `AdminCollection` для внутренних массивов и результатов.
- `AdminString` для id, alias, HTML id и cache keys.
- `AdminCondition` для условий видимости/доступности.
- `Discovery\ClassDiscovery` для поиска потомков `Resource` и standalone-страниц через `MB\Filesystem\Finder\ClassFinder`.

Публичный API принимает обычные `array`, `iterable`, `callable` и `Closure`; разработчику модуля не нужно зависеть от конкретной Collection-реализации.


## v1.0.0 stable API

v1.0.0 фиксирует публичный API AdminKit для реальных Bitrix-модулей. В minor/patch релизах не ломаются public/protected сигнатуры, namespace, базовый CRUD, `FormData`, `GridContext`, `DbResult`, `BulkResult`, Field/Filter/Action API и Resource/CrudResource extension points. Подробная политика описана в `docs/backward-compatibility.md`.

## Lifecycle, transactions и permissions

CRUD операции проходят через единый pipeline: Field normalization/validation, `FormData`, lifecycle hooks, `PermissionContext`, `CrudPersister` и при необходимости `TransactionManager`. Опасные операции (`delete`, row action, bulk action, export) должны проверять CSRF и права на уровне Resource/action.

## Database health и performance

Для диагностики схем используйте `SchemaAwareResource`, `TableSchema`, `DatabaseSchemaInspector`, `TableHealthCheck` и системную страницу health-check. Диагностика read-only по умолчанию. Производительные возможности (`useTotalCount()`, count/options/lookup cache, `QueryGuard`, `maxPageSize()`) включайте консервативно и документируйте в Resource.

## Bitrix UI Field adapters

`EntitySelectorField`, `UserSelectorField`, `IblockElementSelectorField` и `IblockSectionSelectorField` являются адаптерами над Bitrix `ui.entity-selector`/`BX.UI.EntitySelector.TagSelector` и штатными провайдерами. Пакет не реализует собственный selector engine.

## Документация

- Installation: `docs/installation.md`.
- Quick start: `docs/quick-start.md`.
- Resources: `docs/resources.md`.
- CRUD Resource: `docs/crud-resource.md`.
- Database integration: `docs/database.md`.
- Grid: `docs/grid.md`.
- Filters: `docs/filters.md`.
- Forms: `docs/forms.md`.
- Fields: `docs/fields.md`.
- Actions: `docs/actions.md`.
- Bulk actions: `docs/bulk-actions.md`.
- Lifecycle: `docs/lifecycle.md`.
- Transactions: `docs/transactions.md`.
- Permissions: `docs/permissions.md`.
- Performance: `docs/performance.md`.
- Database health: `docs/database-health.md`.
- Export (import UI disabled): `docs/import-export.md`.
- Pages and security: `docs/pages.md`.
- Support packages: `docs/support-packages.md`.
- Backward compatibility: `docs/backward-compatibility.md`.
- Cookbook: `docs/cookbook/`.
- Examples: `examples/`.

## Разработка пакета

```bash
composer install
composer test
composer analyse
composer cs-fix
```

Для CI используется dry-run php-cs-fixer:

```bash
vendor/bin/php-cs-fixer fix --dry-run --diff
```

## Resource pages: custom IndexPage/FormPage/DetailPage

`Resource` describes the entity; `Page` describes a concrete admin view. Use `pages()` to register custom CRUD pages:

```php
final class ProductResource extends CrudResource
{
    public function pages(): iterable
    {
        return [
            ProductIndexPage::class,
            ProductFormPage::class,
            ProductDetailPage::class,
        ];
    }
}
```

The shortcuts `indexFields()`, `formFields()`, and `detailFields()` still work for simple resources. For complex views, override `fields()`, `filters()`, `rowActions()`, `bulkActions()`, query hooks, tabs, and save hooks on the appropriate page class. Do not introduce `indexResource()`, `formResource()`, `detailResource()`, `IndexResource`, `FormResource`, or `DetailResource`; page classes are the extension point.

See `docs/pages.md` for full custom page examples and the `admin_resource` / `admin_page` routing parameters.

## Compatibility notes (v1.x)

- Legacy page class aliases remain available for existing modules:
  - `MB\Bitrix\AdminKit\Page\IndexPage`
  - `MB\Bitrix\AdminKit\Page\FormPage`
  - `MB\Bitrix\AdminKit\Page\DetailPage`
  They proxy to `MB\Bitrix\AdminKit\Page\Crud\*` and are kept for backward compatibility.
- JS runtime is consolidated into `mb.admin.kit` bundle; extension config points to `dist/kit.bundle.js` and `dist/kit.bundle.css`.
