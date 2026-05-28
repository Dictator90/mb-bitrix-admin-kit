# Checkbox

Класс: `MB\Bitrix\AdminKit\Field\Checkbox`.

Назначение: логическое поле.

## Доступные методы

- `values(string $checked, string $unchecked)` — задает значения для включенного и выключенного состояния чекбокса.

Особенность:
- рендерит hidden с unchecked-значением.

Пример:
```php
Checkbox::make('Активен', 'ACTIVE')->values('Y', 'N');
```

## Значения по умолчанию

- `checkedValue = "Y"`
- `uncheckedValue = "N"`
