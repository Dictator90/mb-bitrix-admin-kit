# Heading

Класс: `MB\Bitrix\AdminKit\Component\Heading`.

Назначение: заголовок секции (`h2`-`h6`) с опциональным подзаголовком.

Методы:
- `make(string $text, int $level = 2)`
- `level(int $level)`
- `subtitle(string $subtitle)`

Пример:
```php
Heading::make('Настройки', 3)->subtitle('Параметры интеграции');
```
