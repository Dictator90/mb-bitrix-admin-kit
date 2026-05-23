# UserSelect

Класс: `MB\Bitrix\AdminKit\Field\UserSelect`.

Назначение: готовый selector пользователей (`user-list`) с резолвом подписей.

## Доступные методы

Специфичных методов у `UserSelect` нет: это преднастроенный `DialogSelect`.

Методы `multiple()`, `dependsOn()`, `visibleWhen()` и другие — из общего API `Field`: [field.md](field.md).

Пример:
```php
UserSelect::make('Ответственный', 'RESPONSIBLE_ID');
UserSelect::make('Исполнители', 'EXECUTOR_IDS')->multiple();
```

## Значения по умолчанию

- В конструкторе сразу задаётся `entityId("user-list")`.
- В конструкторе сразу задаётся стандартный `resolveLabels()` через `Bitrix\Main\UserTable`.
- `multiple = false` по умолчанию (включается через `multiple()`).
