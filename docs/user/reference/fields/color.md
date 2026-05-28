# Color

Класс: `MB\Bitrix\AdminKit\Field\Color`.

Назначение: ввод HEX-цвета (color picker + text).

## Доступные методы

- `defaultColor(string $hex)` — задает fallback-цвет, если значение еще не сохранено.

Пример:
```php
Color::make('Цвет', 'COLOR')->defaultColor('#2fc6f6');
```

## Значения по умолчанию

- `defaultColor = "#000000"`.
