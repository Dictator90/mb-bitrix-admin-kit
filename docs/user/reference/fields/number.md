# Number

Класс: `MB\Bitrix\AdminKit\Field\Number`.

Назначение: числовой ввод.

## Доступные методы

- `min(float|int $min, string $message = '')` — задает минимально допустимое значение и валидатор `min`.
- `max(float|int $max, string $message = '')` — задает максимально допустимое значение и валидатор `max`.
- `step(float $step)` — задает шаг изменения значения в HTML input.

Особенности:
- `normalize('') => null`
- numeric строки приводятся к `int|float`

Пример:
```php
Number::make('Цена', 'PRICE')
    ->min(0)
    ->step(0.01)
    ->required();
```

## Значения по умолчанию

- `min = null`
- `max = null`
- `step = null`
