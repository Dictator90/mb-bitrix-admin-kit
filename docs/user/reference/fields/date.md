# Date

Класс: `MB\Bitrix\AdminKit\Field\Date`.

Назначение: дата без времени.

## Доступные методы

- `dateFormat(string $format)` — задает формат представления даты для рендера/превью.

Пример:
```php
Date::make('Дата начала', 'DATE_FROM')->dateFormat('d.m.Y');
```

## Значения по умолчанию

- `dateFormat = "d.m.Y"`.
