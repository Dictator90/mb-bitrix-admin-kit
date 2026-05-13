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
