# Resources

## Что это

`Resource` описывает административную сущность в Admin Kit: identity, поля, фильтры, действия, страницы, права, поведение grid-запроса и lifecycle hooks.

На практике вы создаете класс ресурса и переопределяете методы под ваш сценарий (обычно для D7 ORM сущности).

## Когда использовать

- Когда нужен CRUD-раздел для D7 ORM entity.
- Когда нужно вывести список в `main.ui.grid`.
- Когда нужны формы create/edit и/или detail.
- Когда нужно ограничить доступ и настроить query/performance.

## Какой базовый класс выбрать

| Класс | Когда использовать | Есть CRUD persistence | Типичный сценарий |
|---|---|---:|---|
| `Resource` | Базовая административная сущность или нетиповой сценарий | Нет/не обязательно | Кастомная логика или нестандартный экран |
| `CrudResource` | Нужен CRUD DSL (fields/filters/actions/pages), но сохранение реализуете сами | Нет (`hasCrud(): false`) | Кастомный persistence |
| `DataManagerResource` | Основной вариант для Bitrix D7 ORM CRUD | Да (`hasCrud(): true`) | Стандартный CRUD для `DataManager` |

## Минимальный DataManagerResource

```php
<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\RowAction;
use MB\Bitrix\AdminKit\Field\ID;
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
        ];
    }

    public function formFields(): iterable
    {
        return [
            Text::make('Название', 'NAME')->required(),
        ];
    }

    public function detailFields(): iterable
    {
        return $this->indexFields();
    }

    public function filters(): iterable
    {
        return [
            TextFilter::make('Название', 'NAME')->contains(),
        ];
    }

    public function rowActions(): iterable
    {
        return [RowAction::view(), RowAction::edit(), RowAction::delete()];
    }

    public function bulkActions(): iterable
    {
        return [BulkAction::delete()];
    }
}
```

## Основные extension points

| Метод | Где применяется | Что делает | Когда переопределять |
|---|---|---|---|
| `getId()`, `getTitle()` | menu, page headers | identity ресурса | Когда нужен собственный id/title |
| `getSort()`, `getMenuIcon()`, `isVisibleInMenu()`, `getParentMenuId()`, `group()` | menu/navigation | поведение в меню | Когда настраиваете структуру меню |
| `pages()` | routing/page resolving | список страниц ресурса | Когда нужен свой состав страниц |
| `indexFields()`, `formFields()`, `detailFields()`, `formTabs()` | index/form/detail | набор полей по экрану | Почти всегда в пользовательском ресурсе |
| `filters()` | index filter | фильтры списка | Когда нужен поиск/фильтрация |
| `rowActions()`, `bulkActions()`, `asyncActions()`, `toolbarActions()` | index actions | действия над строками/массивом | Когда добавляете пользовательские действия |
| `indexSelect()`, `indexFilter()`, `indexOrder()`, `indexRuntime()`, `modifyIndexParams()` | GridQueryBuilder/DataLoader | ORM-параметры запроса | Когда настраиваете загрузку данных |
| `canView()`, `canCreate()`, `canUpdate()`, `canDelete()` | page actions + persistence | проверки прав | Когда доступ зависит от роли/контекста |
| `useSidePanel()`, `createInSidePanel()`, `editInSidePanel()`, `detailInSidePanel()` | UX form/detail | режим sidepanel | Когда меняете режим открытия страниц |
| `allowExportByFilter()`, `allowExportAll()`, `maxExportRows()` | export handler | правила экспорта | Когда ограничиваете объем/политику выгрузки |
| `useTotalCount()`, `maxPageSize()`, `bulkChunkSize()` | performance | лимиты/стоимость запросов | На больших таблицах |
| `beforeValidate()/afterValidate()/beforeCreate()/afterCreate()/beforeUpdate()/afterUpdate()/beforeDelete()/afterDelete()` | lifecycle hooks | точки расширения перед/после операций | Для доменных проверок/аудита/событий |

## Resource и D7 ORM

`DataManagerResource` работает через `Bitrix\Main\ORM\Data\DataManager` и требует `dataManagerClass(): string`. Для форм используется `EntityObject` persistence (`newObject()`, `findObject()`) — поэтому это основной путь для стандартного D7 CRUD.

## Resource и Pages

Ресурс не рендерит экран напрямую: экран рендерит страница (`IndexPage`, `FormPage`, `DetailPage`), а ресурс поставляет ей definition (поля/фильтры/действия/query hooks).

Если стандартный набор страниц подходит — оставляйте `pages()` по умолчанию. Если нужен другой набор экранов, переопределите `pages()`.

## Практические сценарии

- Только список: переопределите `indexFields()` и `filters()`, запретите `canCreate()/canUpdate()/canDelete()`.
- Классический CRUD: `DataManagerResource` + `indexFields()/formFields()/filters()/rowActions()`.
- Read-only ресурс: `canCreate()/canUpdate()/canDelete()` вернуть `false`, оставить `detailPage`.
- Ограничение прав по записи: проверять `PermissionContext` в `canUpdate()/canDelete()`.
- Кастомный ORM-фильтр: расширить `indexFilter(GridContext $context)`.
- Большой объем данных: `useTotalCount()` вернуть `false`.

## Связанные разделы

- [Pages](pages.md)
- [Fields](fields.md)
- [Filters](user/reference/filters.md)
- [Grid](grid.md)
- [Actions](actions.md)
- [Bulk actions](bulk-actions.md)
- [Permissions](user/guides/permissions.md)
- [Lifecycle hooks](user/cookbook/lifecycle-hooks.md)
- [Resource selection guide](user/guides/resource-selection.md)
