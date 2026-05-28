# Slug

Класс: `MB\Bitrix\AdminKit\Field\Slug`.

Назначение: текстовое поле для URL-friendly значения (slug) с автогенерацией из других полей.

## Доступные методы

- `from(string|array $sourceColumns)` — задает одно или несколько исходных полей для генерации slug.
- `separator(string $separator)` — задает разделитель (по умолчанию `-`).
- Методы `Text` и базового `Field` также доступны (`required()`, `maxLength()`, `placeholder()`, `readonly()`, `dependsOn()` и т.д.).

## Пример

```php
use MB\Bitrix\AdminKit\Field\Slug;
use MB\Bitrix\AdminKit\Field\Text;

Text::make('Название', 'NAME');

Slug::make('Символьный код', 'CODE')
    ->from('NAME')
    ->maxLength(255);
```

## Как работает обновление

- Slug генерируется из `from(...)` при пустом значении поля.
- При изменении источника slug обновляется реактивно.
- Если пользователь ввел slug вручную (значение отличается от последнего автосгенерированного), автоперезапись отключается.

## Можно ли изменять через `dependsOn()`?

Да. `from(...)` внутри использует `dependsOn(...)`, поэтому поле автоматически обновляется в текущем реактивном flow формы (`adminkit_action=reactive`).

При необходимости можно добавлять и свои зависимости:

```php
Slug::make('Символьный код', 'CODE')
    ->from('NAME')
    ->dependsOn('CATEGORY_ID', static function (Slug $field, mixed $value, array $formData): void {
        unset($value, $formData);
        $field->separator('-');
    });
```

## Значения по умолчанию

- `separator = "-"`.
- `fromColumns = []` (пока не вызван `from(...)`, поле работает как обычный `Text`).

