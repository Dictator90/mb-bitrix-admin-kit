# Как сделать OptionsPage

```php
use MB\Bitrix\AdminKit\Page\Standalone\OptionsPage;

final class SettingsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.demo';

    public static function getId(): string { return 'settings'; }
    public static function getTitle(): string { return 'Настройки'; }
}
```

Подробно: [Reference: Pages](../reference/pages.md)
