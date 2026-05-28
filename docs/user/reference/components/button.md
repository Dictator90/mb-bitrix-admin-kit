# Button

Класс: `MB\Bitrix\AdminKit\Component\Button`.

Назначение: генерация HTML-кнопок Bitrix UI (utility-класс со static-методами).

Методы:
- `save()`, `cancel()`, `add()`
- `primary()`, `secondary()`, `danger()`, `link()`
- `icon()`
- `panel(array $buttons)`

Пример:
```php
echo Button::save();
echo Button::cancel();
echo Button::add('/bitrix/admin/demo.php?action=create', 'Добавить');
```
