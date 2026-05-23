# Tab

Класс: `MB\Bitrix\AdminKit\Component\Layout\Tab`.

Назначение: одна вкладка для контейнера `Tabs` (самостоятельно не рендерится).

Методы:
- `make(string $title, array $items = [])`
- `id(string $id)`
- `field(...)`, `fields(...)`, `with(array $items)`
- `description(string $description)`
- `icon(string $iconClass)`
- `count(int|string $count)`
- `active(bool $active = true)`

Пример:
```php
Tab::make('Уведомления', [
    Text::make('Email', 'EMAIL'),
])->id('notifications')->icon('--mail')->count(2);
```
