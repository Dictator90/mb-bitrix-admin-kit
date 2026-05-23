# Badge

Класс: `MB\Bitrix\AdminKit\Component\Badge`.

Назначение: компактный статусный бейдж.

Методы:
- `make($text, $type = 'neutral')`
- `success()`, `danger()`, `warning()`, `info()`, `neutral()`
- `map(array $valueToTypeMap)`
- `pill()`

Пример:
```php
Badge::make('Активен')->success()->pill();
Badge::make($status)->map(['Y' => 'success', 'N' => 'danger']);
```
