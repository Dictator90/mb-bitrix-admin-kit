# Notification

Класс: `MB\Bitrix\AdminKit\Component\Notification`.

Назначение: JS-уведомления `BX.UI.Notification.Center` и alert fallback.

Методы:
- `success()`, `error()`, `warning()`, `info()`
- `show(string $message, string $type, int $autoclose)` — возвращает JS-строку
- `alert(string $message, string $type)` — возвращает HTML-alert
- `renderOnLoad(...)` — подключает extension и рендерит `<script>`

Пример:
```php
echo Notification::renderOnLoad('Сохранено', Notification::TYPE_SUCCESS, 3000);
```
