# Toolbar

Класс: `MB\Bitrix\AdminKit\Component\Toolbar`.

Назначение: HTML/Bitrix toolbar с левыми/правыми кнопками и заголовком.

Методы:
- `make()`
- `title(string $title)`
- `addButton(string $url, string $text = '')`
- `leftButton(string $html)`, `rightButton(string $html)`
- `render()`
- `renderAddButton(string $url, string $title = '')` (static)

Пример:
```php
Toolbar::make()
    ->title('Товары')
    ->addButton('/bitrix/admin/demo.php?action=create', 'Добавить')
    ->render();
```
