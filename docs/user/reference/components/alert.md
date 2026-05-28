# Alert

Класс: `MB\Bitrix\AdminKit\Component\Alert`.

Назначение: статический inline-alert без JS.

Методы:
- `make($message, $type)`
- `success($message)`, `danger($message)`, `warning($message)`, `info($message)`
- `html(bool $raw = true)`
- `closable(bool $closable = true)`
- `icon(string $iconClass)`

Пример:
```php
Alert::make('Сохранено успешно', Alert::SUCCESS);
Alert::make('Требуется внимание')->warning()->closable();
```
