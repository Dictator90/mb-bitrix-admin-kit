# MB Bitrix Admin Kit

`mb4it/bitrix-admin-kit` — декларативный конструктор административных CRUD-интерфейсов для 1С-Битрикс поверх Bitrix D7 ORM. Пакет помогает описывать ресурсы, поля, фильтры, таблицы `main.ui.grid` и формы создания/редактирования без ручного дублирования типового admin-кода.

## Требования

- PHP 8.2+
- 1С-Битрикс с D7 ORM
- Composer

## Установка

```bash
composer require mb4it/bitrix-admin-kit
```

Пакет использует `mb4it/collections`, `mb4it/stringable` и `mb4it/conditionable` только через внутренние адаптеры:

- `MB\Bitrix\AdminKit\Support\AdminCollection`
- `MB\Bitrix\AdminKit\Support\AdminString`
- `MB\Bitrix\AdminKit\Support\AdminCondition`

Публичный API ресурсов, полей, фильтров и действий принимает стандартные `array`, `iterable`, `callable` и `Closure`; пользователю не нужно возвращать `Collection`.

## Пример D7 ORM таблицы

```php
<?php

namespace Local\Products;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Data\DataManager;

final class ProductTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'local_product';
    }

    public static function getMap(): array
    {
        return [
            (new Entity\IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
            new Entity\StringField('NAME', ['required' => true]),
            new Entity\IntegerField('SORT'),
            new Entity\StringField('ACTIVE'),
        ];
    }
}
```

## Пример ресурса

```php
<?php

namespace Local\Admin\Resource;

use Local\Products\ProductTable;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Number;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Filter\Types\SelectFilter;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Resource\CrudResource;

final class ProductResource extends CrudResource
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
            Number::make('Сортировка', 'SORT'),
            Select::make('Активность', 'ACTIVE')->options(['Y' => 'Да', 'N' => 'Нет']),
        ];
    }

    public function formFields(): iterable
    {
        return [
            Text::make('Название', 'NAME')->required()->placeholder('Название товара'),
            Number::make('Сортировка', 'SORT')->default(500),
            Select::make('Активность', 'ACTIVE')->options(['Y' => 'Да', 'N' => 'Нет'])->default('Y'),
        ];
    }

    public function filters(): iterable
    {
        return [
            TextFilter::make('Название', 'NAME'),
            SelectFilter::make('Активность', 'ACTIVE')->options(['Y' => 'Да', 'N' => 'Нет']),
        ];
    }

    public function defaultSort(): array
    {
        return ['SORT' => 'ASC', 'ID' => 'DESC'];
    }

    public function modifyIndexParams(array $params, GridContext $context): array
    {
        // Главная точка расширения для реальных D7 ORM-запросов:
        // можно добавить runtime-поля, сложные фильтры или ограничения прав.
        return $params;
    }
}
```

## Пример admin-файла

```php
<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Local\Admin\Resource\ProductResource;

$resource = new ProductResource();
$action = $_REQUEST['action'] ?? 'index';
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : null;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

match ($action) {
    'add' => $resource->formPage()->render(),
    'edit' => $resource->formPage($id)->render(),
    default => $resource->indexPage()->render(),
};

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

## Пример меню

```php
<?php

use Local\Admin\Resource\ProductResource;

return [
    'parent_menu' => 'global_menu_content',
    'section' => ProductResource::getId(),
    'sort' => ProductResource::getSort(),
    'text' => 'Товары',
    'title' => 'Товары',
    'url' => 'local_product_admin.php?page=' . ProductResource::getId(),
    'icon' => ProductResource::getMenuIcon(),
];
```

## Базовые сценарии

- **Список**: `IndexPage` строит `GridContext`, передает его в `GridQueryBuilder`, получает параметры `select`, `filter`, `order`, `limit`, `offset`, `runtime` и вызывает `CrudResource::modifyIndexParams()`.
- **Фильтр**: фильтры формируют описание для `main.ui.filter` и применяют непустые значения к ORM-фильтру. `0` и `'0'` считаются значимыми значениями.
- **Создание**: `FormPage` проверяет sessid, собирает значения полей, вызывает `normalize()`, `runValidation()` и затем `CrudResource::createItem()`.
- **Редактирование**: форма загружает запись через `findItem()`, нормализует POST и сохраняет через `CrudResource::updateItem()`.
- **Удаление**: delete/action POST-операции проверяют `check_bitrix_sessid()`, а URL строятся централизованно через `UrlGenerator` и `http_build_query()`.

## Support-адаптеры

```php
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\AdminCondition;
use MB\Bitrix\AdminKit\Support\AdminString;

$fields = AdminCollection::make([Text::make('Название', 'NAME')]);
$gridId = AdminString::gridId(ProductResource::class);
$visible = AdminCondition::evaluate(fn(array $ctx) => $ctx['canView'], ['canView' => true]);
```

Глобальные helper-функции `collect()`, `str()` и `condition()` внутри AdminKit не используются.

## v0.2.0: ORM grid customization

`CrudResource` can now customize every part of the index ORM query without replacing the grid layer. The v0.1.0 hooks (`defaultSelect()`, `defaultFilter()`, `defaultSort()`, `runtimeFields()`, and `modifyIndexParams()`) still work, and v0.2.0 adds context-aware hooks:

```php
public function indexSelect(GridContext $context): array;
public function indexFilter(GridContext $context): array;
public function indexOrder(GridContext $context): array;
public function indexRuntime(GridContext $context): array;
public function beforeIndexQueryParams(array $params, GridContext $context): array;
public function afterIndexRows(array $rows, GridContext $context): array;
public function mapIndexRow(array $row, GridContext $context): array;
```

The query builder assembles ORM params in this order: index fields, default/index select, UI/default/index filters, UI/default/index order, runtime fields, pagination, `beforeIndexQueryParams()`, then the legacy `modifyIndexParams()` hook.

### Runtime fields and ReferenceField

Runtime fields are passed directly to Bitrix D7 ORM `DataManager::getList()` through the `runtime` key. The grid layer does not know join business logic, so Bitrix runtime field objects such as `ReferenceField` can be returned as-is:

```php
use Bitrix\Main\ORM\Fields\Relations\Reference as ReferenceField;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserTable;
use MB\Bitrix\AdminKit\Grid\GridContext;

public function indexRuntime(GridContext $context): array
{
    return [
        new ReferenceField(
            'USER',
            UserTable::class,
            Join::on('this.USER_ID', 'ref.ID')
        ),
    ];
}

public function indexSelect(GridContext $context): array
{
    return ['USER_NAME' => 'USER.NAME', 'USER_LAST_NAME' => 'USER.LAST_NAME'];
}
```

### Computed columns and displayUsing

Computed fields are intended for values that are derived after rows are fetched. They are displayed in grid/detail pages, but they are not automatically added to ORM `select`, sorting, or filtering:

```php
use MB\Bitrix\AdminKit\Field\Text;

Text::make('Пользователь', 'USER_FULL_NAME')
    ->computed(function (array $row): string {
        return trim(($row['USER_NAME'] ?? '') . ' ' . ($row['USER_LAST_NAME'] ?? ''));
    })
    ->displayUsing(function (mixed $value, array $row, array $context): string {
        return $value ?: '—';
    });
```

`displayUsing()` receives the raw value, the full row, and display context (`page` and `field`) and is used by grid/detail rendering.

### Custom filters and CallbackFilter

Filters implement `applyToOrmFilter(array $filter, mixed $value, GridContext $context): array`. Empty values (`null`, `''`, `[]`) are ignored, while `0`, `'0'`, and `false` are treated as intentional filter values.

```php
use MB\Bitrix\AdminKit\Filter\Types\CallbackFilter;
use MB\Bitrix\AdminKit\Grid\GridContext;

CallbackFilter::make('Поиск', 'SEARCH')
    ->apply(function (array $filter, mixed $value, GridContext $context): array {
        $filter[] = [
            'LOGIC' => 'OR',
            '%NAME' => $value,
            '%CODE' => $value,
        ];

        return $filter;
    });
```

Built-in filter operators:

- `TextFilter`: `exact()`, `contains()`, `startsWith()`, `endsWith()`.
- `NumberFilter`: `exact()`, `range()`, `greaterThan()`, `lessThan()`.
- `SelectFilter`: `exact()`, `multiple()`.
- `DateFilter`: `exact()`, `range()`.

### Row mapping hooks

Use `afterIndexRows()` to post-process the fetched row list and `mapIndexRow()` to transform each row before computed fields and `displayUsing()` are applied:

```php
public function afterIndexRows(array $rows, GridContext $context): array
{
    return array_values($rows);
}

public function mapIndexRow(array $row, GridContext $context): array
{
    $row['BADGE'] = $row['ACTIVE'] === 'Y' ? 'Active' : 'Inactive';

    return $row;
}
```
