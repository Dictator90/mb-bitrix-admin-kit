# Password

Класс: `MB\Bitrix\AdminKit\Field\Password`.

Назначение: ввод пароля с безопасным поведением при редактировании.

## Доступные методы

- `oldValue(bool $show = true)` — включает/выключает показ текущего сохраненного значения в поле при редактировании.

Особенности:
- по умолчанию может показывать существующее значение с toggle show/hide;
- пустое значение на edit сохраняет старый пароль (`preserveStoredValueWhenEmpty()`).

Пример:
```php
Password::make('Пароль', 'API_SECRET')
    ->oldValue(false)
    ->placeholder('Оставьте пустым, чтобы не менять');
```

## Значения по умолчанию

- `showOldValue = true` (на форме показывается сохранённое значение с toggle show/hide).
- `preserveStoredValueWhenEmpty() = true` (пустой submit не затирает сохранённый пароль).
- Поле скрыто на `index` и `detail` по умолчанию.
