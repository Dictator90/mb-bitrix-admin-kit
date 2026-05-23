# Textarea

Класс: `MB\Bitrix\AdminKit\Field\Textarea`.

Назначение: многострочный текст.

## Доступные методы

- `rows(int $rows)` — задает высоту textarea в строках.

Пример:
```php
Textarea::make('Описание', 'DESCRIPTION')->rows(8);
```

## Значения по умолчанию

- `rows = 5`.
