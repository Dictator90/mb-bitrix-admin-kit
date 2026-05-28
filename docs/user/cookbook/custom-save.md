# Как кастомизировать save

```php
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Form\FormData;

public function beforeCreate(FormData $data, DbOperationContext $context): FormData
{
    return $data;
}
```

См. также: [Guide: Формы и lifecycle](../guides/forms-lifecycle.md)
