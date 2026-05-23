# Text

Класс: `MB\Bitrix\AdminKit\Field\Text`.

Назначение: однострочный текст.

## Доступные методы

- `maxLength(int $max, string $message = '')` — ограничивает максимальную длину ввода и добавляет валидатор длины.

Пример:
```php
Text::make('Название', 'NAME')
    ->required()
    ->maxLength(255)
    ->placeholder('Введите название');
```

## Значения по умолчанию

- `maxLength = null` (ограничение длины не задано).
