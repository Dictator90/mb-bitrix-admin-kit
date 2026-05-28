# Column

Класс: `MB\Bitrix\AdminKit\Component\Layout\Column`.

Назначение: колонка для `Grid`.

Методы:
- `make(array $children = [])`
- `span(int $span)` — ширина 1..12
- `smSpan(int $span)` — адаптивная ширина (через data-attr)
- `offset(int $cols)` — смещение

Пример:
```php
Column::make([
    Text::make('Название', 'NAME'),
])->span(6)->smSpan(12);
```
