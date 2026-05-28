# Tabs

Класс: `MB\Bitrix\AdminKit\Component\Layout\Tabs`.

Назначение: контейнер вкладок (`Tab`) с рендером через runtime `mb.admin.kit`.

Методы:
- `make(array $tabs = [])`
- `remember(bool $remember = true, ?string $storageKey = null)` — запоминать активную вкладку
- `withRememberedActiveTab(?string $tabId)`

Пример:
```php
Tabs::make([
    Tab::make('Основное', [Text::make('Название', 'NAME')])->active(),
    Tab::make('Дополнительно', [Switcher::make('Активность', 'ACTIVE')])->id('extra'),
]);
```
