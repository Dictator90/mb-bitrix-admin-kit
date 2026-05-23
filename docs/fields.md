# Условная логика полей

`required()`, `readonly()`, `visible()` поддерживают `Closure` с `FieldConditionContext`.

```php
Text::make('Комментарий', 'COMMENT')
    ->required(fn (FieldConditionContext $ctx): bool => $ctx->is('ACTIVE', 'Y'));
```

`canSee()` — alias для `visible()` (в стиле MoonShine/Nova).

`when(condition, modifier, dependsOn)` применяет произвольные модификаторы поля.

`dependsOn` включает server + reactive режим; без него условие только серверное.

Shortcut API сохранен: `requiredWhen()`, `readonlyWhen()`, `visibleWhen()`.
