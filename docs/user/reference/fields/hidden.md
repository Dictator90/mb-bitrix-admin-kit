# Hidden

Класс: `MB\Bitrix\AdminKit\Field\Hidden`.

Назначение: скрытое поле формы.

## Доступные методы

Специфичных fluent-методов у `Hidden` нет.

Общий API `Field`: [field.md](field.md).

Особенности:
- скрывается на `index` и `detail`.

Пример:
```php
Hidden::make('Внутренний ключ', 'INTERNAL_KEY')->default('x');
```

## Значения по умолчанию

- `sortable = false` (переопределено в конструкторе).
- Поле скрыто на `index` и `detail` по умолчанию.
