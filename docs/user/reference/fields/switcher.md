# Switcher

Класс: `MB\Bitrix\AdminKit\Field\Switcher`.

Назначение: Bitrix UI switch.

## Доступные методы

- `values(string $checked, string $unchecked)` — задает значения вкл/выкл для сериализации и рендера.
- `isCheckedValue(mixed $value, string $checkedValue = 'Y')` — helper, определяющий, считать ли текущее значение включенным.

Особенность:
- `normalize()` всегда приводит к `checkedValue|uncheckedValue`.

Пример:
```php
Switcher::make('Активность', 'ACTIVE')->values('Y', 'N')->default('Y');
```

## Значения по умолчанию

- `checkedValue = "Y"`
- `uncheckedValue = "N"`
