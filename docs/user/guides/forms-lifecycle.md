# Формы и lifecycle

## Когда использовать

Когда настраиваете сохранение формы, валидацию, хуки до/после persistence.

## Минимальный пример

```php
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Form\FormData;

public function beforeValidate(FormData $data, DbOperationContext $context): FormData
{
    // Изменяем normalized/validated-данные до финального сохранения
    return $data;
}

public function afterUpdate(FormData $data, DbOperationContext $context, mixed $id): void
{
    // Пост-действия
}
```

`FormData` хранит стадии: `raw`, `normalized`, `validated`, `errors`.

Канонический список lifecycle-хуков:
- `beforeValidate` / `afterValidate`
- `beforeCreate` / `afterCreate`
- `beforeUpdate` / `afterUpdate`
- `beforeDelete` / `afterDelete`
- `beforeMassDelete` / `afterMassDelete`

## Ограничения

- Для `DataManagerResource` сохранение идет через `EntityObject` flow.
- Устаревшие хуки допустимы только как BC-слой, новые расширения пишите на v0.3+ API.

## См. также

- [Cookbook: lifecycle hooks](../cookbook/lifecycle-hooks.md)
- [Reference: Resources](../reference/resources.md)
