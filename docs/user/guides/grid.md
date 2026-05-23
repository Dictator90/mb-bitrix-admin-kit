# Грид

## Когда использовать

Когда настраиваете список: колонки, фильтры, сортировку, runtime-поля, группировку.

## Минимальный пример

```php
use Bitrix\Main\ORM\Fields\Relations\Reference;
use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\GridContext;

public function indexFields(): iterable
{
    return [
        ID::make('ID'),
        Text::make('Название', 'NAME'),
        Text::make('Раздел', 'SECTION_NAME'),
    ];
}

public function indexSelect(GridContext $context): array
{
    return ['ID', 'NAME', 'SECTION_NAME' => 'SECTION.NAME'];
}

public function indexRuntime(GridContext $context): array
{
    return [
        new Reference('SECTION', SectionTable::class, ['=this.SECTION_ID' => 'ref.ID']),
    ];
}
```

`GridQueryBuilder` — единственный источник ORM-параметров `select/filter/order/runtime/limit/offset`.

## Ограничения

- Не переносите ORM query-building в `Grid`/`IndexPage` UI-слой.
- Synthetic row IDs `group:*` и `item:*` не должны попадать в inline/bulk операции.

## См. также

- [Bulk actions](bulk-actions.md)
- [Reference: Fields](../reference/fields/README.md)
