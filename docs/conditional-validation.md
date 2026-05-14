# Conditional validation

Fields support conditionable helpers powered by Admin Kit conditions:

```php
Text::make('Email', 'EMAIL')->requiredWhen('SUBSCRIBE', '=', 'Y');

Text::make('External URL', 'EXTERNAL_URL')
    ->requiredWhen(AdminCondition::make()->where('TYPE', '=', 'external'));
```

`requiredWhen()`, `readonlyWhen()`, and `visibleWhen()` accept short `field/operator/value` conditions, `ConditionTree` instances, or closures. Admin Kit uses `AdminCondition`; avoid global condition helpers in resources.
