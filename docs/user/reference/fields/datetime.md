# DateTime

Класс: `MB\Bitrix\AdminKit\Field\DateTime`.

Назначение: дата и время.

## Доступные методы

- `dateFormat(string $format)` — задает формат отображения даты и времени.

Пример:
```php
DateTime::make('Создано', 'DATE_CREATE')->dateFormat('d.m.Y H:i');
```

## Значения по умолчанию

- `dateFormat = "d.m.Y H:i"`.
