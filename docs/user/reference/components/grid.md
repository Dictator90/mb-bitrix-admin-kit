# Grid

Класс: `MB\Bitrix\AdminKit\Component\Layout\Grid`.

Назначение: CSS-grid контейнер (по умолчанию 12 колонок).

Методы:
- `make(array $children = [])`
- `columns(int $columns)`
- `gap(int $px)`

Пример:
```php
Grid::make([
    Column::make([...])->span(6),
    Column::make([...])->span(6),
])->gap(16);
```
