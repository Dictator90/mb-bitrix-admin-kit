# Flex

Класс: `MB\Bitrix\AdminKit\Component\Layout\Flex`.

Назначение: flex-контейнер для горизонтальной/вертикальной раскладки.

Методы:
- `make(array $children = [])`
- `justify(string $justify)` (`start/end/center/between/around/evenly`)
- `align(string $align)` (`start/end/center/stretch/baseline`)
- `gap(int $px)`
- `nowrap()`
- `column()`

Пример:
```php
Flex::make([
    Text::make('Имя', 'NAME'),
    Text::make('Email', 'EMAIL'),
])->gap(12)->justify('between');
```
