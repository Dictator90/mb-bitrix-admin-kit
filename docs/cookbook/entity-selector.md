# Как подключить EntitySelectorField

```php
EntitySelectorField::make('User', 'USER_ID')
    ->entity('user')
    ->multiple(false);
```

AdminKit оборачивает Bitrix `ui.entity-selector`/`BX.UI.EntitySelector.TagSelector` и не реализует собственный selector engine.
