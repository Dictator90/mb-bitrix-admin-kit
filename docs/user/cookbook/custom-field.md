# Custom Field

## Задача

Добавить поле, которого нет среди стандартных `Field`.

## Когда использовать

Когда нужен особый рендер/нормализация значения, но базовые `Text/Select/...` не подходят.

## Решение

Наследуйте класс от `MB\Bitrix\AdminKit\Field\Field` и реализуйте минимум `renderFormField()`. При необходимости переопределяйте `renderIndex()`/`renderDetail()` и `normalize()`.

## Полный пример

```php
use MB\Bitrix\AdminKit\Field\Field;

final class JsonPreviewField extends Field
{
    public function renderFormField(mixed $value = null): string
    {
        $encoded = htmlspecialcharsbx((string)json_encode($value, JSON_UNESCAPED_UNICODE));

        return '<textarea name="' . htmlspecialcharsbx($this->getColumn()) . '">' . $encoded . '</textarea>';
    }

    public function normalize(mixed $value): mixed
    {
        if (is_string($value) && $value !== '') {
            return json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        return [];
    }
}
```

## Как это работает

`Field` уже содержит общие цепочки (`required`, `displayUsing`, `sortable`, import/export flags), поэтому расширение остается совместимым с grid/form/detail/options.

## Что важно учесть

- Не вставляйте тяжелую DOM/JS-логику через большие heredoc в PHP.
- Сложный JS лучше выносить в Bitrix extension.
- Нормализация должна быть явной и предсказуемой.

## Связанные разделы

- [Fields](../../fields.md)
- [Reference: Field base](../reference/fields/field.md)
