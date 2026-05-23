# Box

Класс: `MB\Bitrix\AdminKit\Component\Layout\Box`.

Назначение: секционный контейнер с рамкой, заголовком и optional collapse.

Методы:
- `make($titleOrChildren, array $children = [])`
- `title(string $title)`
- `collapsible(bool $collapsed = false)`

Пример:
```php
Box::make('Основное', [
    Text::make('Название', 'NAME'),
])->collapsible();
```
