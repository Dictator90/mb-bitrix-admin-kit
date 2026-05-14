# OptionsPage v0.8.0

Extend `MB\Bitrix\AdminKit\Pages\OptionsPage` to create a module settings page backed by Bitrix `Option` storage.

```php
final class ModuleSettingsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.module';

    public static function getId(): string { return 'settings'; }
    public static function getTitle(): string { return 'Settings'; }

    public function fields(): iterable
    {
        return [
            Text::make('API token', 'api_token')->required(),
            Switcher::make('Enable exchange', 'exchange_enabled'),
        ];
    }
}
```

`fields()` is the simple API. Existing `components()`-based layouts and tabs remain supported.
