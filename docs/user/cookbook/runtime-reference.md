# Как добавить runtime ReferenceField

```php
use Bitrix\Main\ORM\Fields\Relations\Reference;
use MB\Bitrix\AdminKit\Grid\GridContext;

public function indexRuntime(GridContext $context): array
{
    return [
        new Reference('CATEGORY', CategoryTable::class, ['=this.CATEGORY_ID' => 'ref.ID']),
    ];
}
```

См. также: [Guide: Grid](../guides/grid.md)
