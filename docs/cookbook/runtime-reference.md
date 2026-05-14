# Как добавить runtime ReferenceField

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

public function indexSelect(GridContext $context): array
{
    return ['CATEGORY_NAME' => 'CATEGORY.NAME'];
}
```

Передавайте Bitrix runtime objects в ORM params без бизнес-join в grid layer.
