# Первая standalone-страница

## Когда использовать

Когда нужна не-CRUD страница: настройки, дашборд, отчет, интеграция.

## Минимальный пример

```php
<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Page\Standalone\OptionsPage;

final class SettingsPage extends OptionsPage
{
    protected bool $multiSite = true;

    public static function getId(): string
    {
        return 'settings';
    }

    public static function getTitle(): string
    {
        return 'Настройки';
    }

    public function fields(): iterable
    {
        return [
            Text::make('Email для уведомлений', 'admin_email')->required(),
        ];
    }
}
```

## Ограничения

- Используйте namespace `MB\Bitrix\AdminKit\Page\Standalone\*`.
- `OptionsPage` сохраняет через `Bitrix\Main\Config\Option`.

## См. также

- [Fields](../reference/fields/README.md)
- [SidePanel, меню и toolbar](../guides/ui-integration.md)
- [Reference: Pages](../reference/pages.md)
