# Как добавить поле

Добавьте Field в `indexFields()` для грида и в `formFields()` для формы:

```php
Text::make('Name', 'NAME')->required()->placeholder('Product name');
Select::make('Type', 'TYPE')->options(['simple' => 'Simple', 'service' => 'Service']);
Switcher::make('Active', 'ACTIVE')->values('Y', 'N')->default('Y');
```

Если нужен новый тип, расширяйте существующий `Field`, а не создавайте параллельную систему полей.
