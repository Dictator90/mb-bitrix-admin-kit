# Условная валидация

Поля поддерживают conditionable-хелперы на базе условий Admin Kit:

```php
Text::make('Email', 'EMAIL')->requiredWhen('SUBSCRIBE', '=', 'Y');

Text::make('External URL', 'EXTERNAL_URL')
    ->requiredWhen(AdminCondition::make()->where('TYPE', '=', 'external'));
```

`requiredWhen()`, `readonlyWhen()` и `visibleWhen()` принимают короткие условия `field/operator/value`, экземпляры `ConditionTree` или замыкания. Admin Kit использует `AdminCondition`; избегайте глобальных condition-хелперов в ресурсах.
