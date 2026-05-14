# Как сделать страницу настроек

```php
final class SettingsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.demo';

    public static function title(): string { return 'Settings'; }

    protected function fields(): iterable
    {
        return [Text::make('Token', 'token')->private()];
    }
}
```

OptionsPage сохраняет значения через Bitrix `Option` и поддерживает multi-site режим.
