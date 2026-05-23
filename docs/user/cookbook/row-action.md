# Как добавить row action

```php
use MB\Bitrix\AdminKit\Action\RowAction;

public function rowActions(): iterable
{
    return [RowAction::edit(), RowAction::delete()];
}
```

См. также: [Reference: Actions](../reference/actions.md)
