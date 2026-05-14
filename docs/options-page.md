# OptionsPage

`MB\Bitrix\AdminKit\Pages\OptionsPage` is a standalone page that saves values
to Bitrix `b_option` / `b_option_site` via `Bitrix\Main\Config\Option`.

> **Note:** `MB\Bitrix\AdminKit\Page\OptionsPage` (singular `Page`) is deprecated.
> Always extend `Pages\OptionsPage` (plural).

---

## Minimal example

```php
<?php

namespace Vendor\Module\Admin;

use MB\Bitrix\AdminKit\Field\Password;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Pages\OptionsPage;

final class ModuleSettingsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.module';

    public static function getId(): string    { return 'settings'; }
    public static function getTitle(): string { return 'Module settings'; }

    public function fields(): iterable
    {
        return [
            Text::make('API key', 'api_key')->required(),
            Password::make('Secret', 'api_secret'),
            Switcher::make('Debug mode', 'debug')->values('Y', 'N')->default('N'),
        ];
    }
}
```

Register in the admin file:

```php
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require_once __DIR__ . '/../include.php';

use Vendor\Module\Admin\ModuleSettingsPage;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
(new ModuleSettingsPage())->render();
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

---

## `fields()` vs `components()`

`fields()` is the public API — override it and return any mix of fields and
layout components. `components()` is an internal alias that calls `fields()`.

```php
public function fields(): iterable
{
    return [
        Text::make('Host', 'smtp_host'),
        Number::make('Port', 'smtp_port')->default(587),
    ];
}
```

---

## Layout components inside `fields()`

Any `ComponentContract` (Box, Collapse, Tabs, …) may appear in the list.
Fields inside layout components are discovered automatically for save/load.

### Box

```php
use MB\Bitrix\AdminKit\Component\Layout\Box;

public function fields(): iterable
{
    return [
        Text::make('API Key', 'api_key'),
        Box::make('Debug', [
            Switcher::make('Enabled', 'debug')->values('Y', 'N'),
            Text::make('Log path', 'log_path'),
        ]),
    ];
}
```

### Collapse

```php
use MB\Bitrix\AdminKit\Component\Layout\Collapse;

Collapse::make('Advanced', [
    Text::make('Proxy host', 'proxy_host'),
    Number::make('Proxy port', 'proxy_port'),
])
```

---

## Tabbed layout

Wrap tabs in `Tabs::make([...])` — `Tab` cannot render standalone.

```php
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Layout\Tabs;

public function fields(): iterable
{
    return [
        Tabs::make([
            Tab::make('General', [
                Text::make('API Key', 'api_key'),
                Switcher::make('Active', 'active')->values('Y', 'N'),
            ])->active(),

            Tab::make('Notifications', [
                Text::make('Webhook URL', 'webhook_url'),
                Text::make('From e-mail', 'from_email'),
            ])->id('notifications'),

            Tab::make('Advanced', [
                Text::make('Proxy host', 'proxy_host'),
            ])->icon('--settings')->count(1),
        ]),
    ];
}
```

**Tab options:**

| Method | Description |
|--------|-------------|
| `active()` | Mark as initially active (first tab is active by default if none set) |
| `id(string)` | Explicit DOM/routing ID; auto-derived from title otherwise |
| `description(string)` | Tooltip shown on the tab header |
| `icon(string)` | Icon-set CSS class (`'--settings'`, `'--lock'`, …); requires `mb.ui.tabs` |
| `count(int\|string)` | Badge counter (`3`, `'99+'`); requires `mb.ui.tabs` |
| `field(...)` / `fields(...)` / `with([...])` | Add items after construction |

---

## Per-site options (`multiSite`)

When `$multiSite = true`, the page renders one form tab per active Bitrix site.
Values are saved to `b_option_site` scoped to the site ID.

```php
final class SiteOptionsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.module';
    protected bool   $multiSite = true;

    public static function getId(): string    { return 'site_settings'; }
    public static function getTitle(): string { return 'Site settings'; }

    public function fields(): iterable
    {
        return [
            Text::make('Sender name', 'sender_name'),
            Text::make('Sender email', 'sender_email'),
        ];
    }
}
```

---

## Reactive fields (`dependsOn` / `onChange`)

Fields with `dependsOn()` are re-rendered via AJAX when the source field changes.
`OptionsPage` handles the AJAX endpoint automatically — no additional routing is needed.

```php
use MB\Bitrix\AdminKit\Field\IblockElementSelect;
use MB\Bitrix\AdminKit\Field\IblockSelect;
use MB\Bitrix\AdminKit\Field\Select;

public function fields(): iterable
{
    return [
        IblockSelect::make('Iblock', 'IBLOCK_ID')
            ->onChange('ELEMENT_ID', fn ($v) => null),

        IblockElementSelect::make('Element', 'ELEMENT_ID')
            ->dependsOn('IBLOCK_ID'),

        Select::make('Category', 'CATEGORY_ID')
            ->options(['a' => 'A', 'b' => 'B'])
            ->onChange('SUB_ID', fn ($v) => null),

        Select::make('Sub-category', 'SUB_ID')
            ->dependsOn('CATEGORY_ID', function (Select $field, mixed $val): void {
                $field->options(SubcategoryRepo::optionsFor((int)$val));
            }),
    ];
}
```

---

## `visibleWhen` — conditional field display

Fields (and layout components that implement `getVisibleWhen()`) are hidden
or shown based on another field's value. The toggle is client-side (CSS + JS
driven by `data-visible-when`).

```php
Switcher::make('Use proxy', 'use_proxy')->values('Y', 'N'),

Text::make('Proxy host', 'proxy_host')
    ->visibleWhen('use_proxy', 'Y'),        // 2-arg shorthand: column, value

Text::make('Proxy port', 'proxy_port')
    ->visibleWhen('use_proxy', '=', 'Y'),  // explicit operator form

Select::make('Auth type', 'auth_type')
    ->visibleWhen('use_proxy', 'Y'),

Box::make('Proxy credentials', [
    Text::make('Username', 'proxy_user'),
    Password::make('Password', 'proxy_pass'),
])->visibleWhen('auth_type', 'basic'),
```

---

## Abstract requirements

`OptionsPage` only requires two static methods:

```php
public static function getId(): string;     // unique page slug
public static function getTitle(): string;  // admin panel title
```

And `$moduleId` must be set. Everything else is optional.
