# Как сделать страницу настроек

Расширьте `MB\Bitrix\AdminKit\Page\Standalone'OptionsPage` и объявите `$moduleId`.

```php
<?php

namespace Vendor\Module\Admin;

use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Layout\Tabs;
use MB\Bitrix\AdminKit\Field\Password;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Page\Standalone\OptionsPage;

final class SettingsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.module';

    public static function getId(): string    { return 'settings'; }
    public static function getTitle(): string { return 'Module settings'; }

    public function fields(): iterable
    {
        return [
            Tabs::make([
                Tab::make('API', [
                    Text::make('API key', 'api_key')->required(),
                    Password::make('Secret', 'api_secret'),
                ])->active(),

                Tab::make('Options', [
                    Switcher::make('Debug', 'debug')->values('Y', 'N')->default('N'),
                ])->id('options'),
            ]),
        ];
    }
}
```

Подключение в admin-файле:

```php
<?php
use Vendor\Module\Admin\SettingsPage;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

global $APPLICATION;
$adminPage->hideTitle();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

\Bitrix\Main\Loader::includeModule('vendor.module');

// Если админ файл должен рендерить только одну страницу
(new SettingsPage())->render();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

`OptionsPage` сохраняет значения через `Bitrix\Main\Config\Option` (`b_option`).
Полная справка — в [docs/options-page.md](../options-page.md).
