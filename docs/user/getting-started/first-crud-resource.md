# Первый ORM CRUD-ресурс

## Когда использовать

Когда нужен стандартный CRUD над D7 `DataManager`.

## Минимальный пример

```php
<?php

declare(strict_types=1);

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

    public function formFields(): iterable
    {
        return [
            Text::make('Название', 'NAME')->required(),
        ];
    }
}
```

## Ограничения

- Для ORM persistence используйте именно `DataManagerResource`.
- `CrudResource` сам по себе — DSL-уровень без ORM persistence (`hasCrud(): false`).

## См. также

- [Как выбрать базовый класс ресурса](../guides/resource-selection.md)
- [Reference: Resources](../reference/resources.md)
