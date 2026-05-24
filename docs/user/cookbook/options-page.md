# Рецепт: страница настроек OptionsPage

## Задача

Сделать standalone-страницу настроек модуля с сохранением через Bitrix Option API.

## Решение

```php
<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin\Page;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Page\Standalone\OptionsPage;

final class SettingsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.demo';

    public static function getId(): string
    {
        return 'vendor_demo_settings';
    }

    public static function getTitle(): string
    {
        return 'Настройки модуля';
    }

    public function fields(): iterable
    {
        return [
            Text::make('API URL', 'api_url')->required(),
            Switcher::make('Включено', 'enabled')->default(true),
        ];
    }
}
```

## Важные замечания

- `moduleId` должен быть задан, иначе страница покажет ошибку;
- ключи опций формируются из колонок полей (`api_url`, `enabled`);
- для per-site режима включайте `protected bool $multiSite = true;`.

## Ссылки

- [OptionsPage (guide)](../../options-page.md)
- [Pages reference](../reference/pages.md)
