# OptionsPage

`MB\Bitrix\AdminKit\Pages\OptionsPage` — standalone-страница, сохраняющая значения
в Bitrix `b_option` / `b_option_site` через `Bitrix\Main\Config\Option`.

> **Примечание:** `MB\Bitrix\AdminKit\Page\OptionsPage` (единственное `Page`) устарел.
> Всегда расширяйте `Pages\OptionsPage` (множественное число).

---

## Минимальный пример

```php
<?php

namespace Vendor\Module\Admin;

use MB\Bitrix\AdminKit\Field\Password;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Page\Standalone\OptionsPage;

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

Регистрация в admin-файле:

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

`fields()` — публичный API: переопределите его и верните любую смесь полей и
layout-компонентов. `components()` — внутренний alias, вызывающий `fields()`.

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

## Layout-компоненты внутри `fields()`

В списке может быть любой `ComponentContract` (Box, Collapse, Tabs, …).
Поля внутри layout-компонентов автоматически участвуют в save/load.

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

## Вкладочная раскладка

Оборачивайте вкладки в `Tabs::make([...])` — `Tab` не рендерится отдельно.

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

**Опции Tab:**

| Метод | Описание |
|--------|-------------|
| `active()` | Активна при загрузке (первая вкладка активна по умолчанию, если ни одна не задана) |
| `id(string)` | Явный DOM/routing ID; иначе выводится из title |
| `description(string)` | Подсказка в заголовке вкладки |
| `icon(string)` | CSS-класс icon-set (`'--settings'`, `'--lock'`, …); рендер через Tabs `mb.admin.kit` |
| `count(int\|string)` | Счётчик-бейдж (`3`, `'99+'`); рендер через Tabs `mb.admin.kit` |
| `field(...)` / `fields(...)` / `with([...])` | Добавить элементы после создания |

---

## Опции по сайтам (`multiSite`)

При `$multiSite = true` страница рендерит одну вкладку формы на каждый активный сайт Bitrix.
Значения сохраняются в `b_option_site` с привязкой к ID сайта.

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

## Реактивные поля (`dependsOn` / `onChange`)

Поля с `dependsOn()` перерисовываются по AJAX при смене исходного поля.
`OptionsPage` обрабатывает AJAX endpoint автоматически — дополнительная маршрутизация не нужна.

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

## `visibleWhen` — условный показ полей

Поля (и layout-компоненты с `getVisibleWhen()`) скрываются или показываются
по значению другого поля. Переключение на клиенте (CSS + JS
через `data-visible-when`).

```php
Switcher::make('Use proxy', 'use_proxy')->values('Y', 'N'),

Text::make('Proxy host', 'proxy_host')
    ->visibleWhen('use_proxy', 'Y'),        // краткая форма: колонка, значение

Text::make('Proxy port', 'proxy_port')
    ->visibleWhen('use_proxy', '=', 'Y'),  // явный оператор

Select::make('Auth type', 'auth_type')
    ->visibleWhen('use_proxy', 'Y'),

Box::make('Proxy credentials', [
    Text::make('Username', 'proxy_user'),
    Password::make('Password', 'proxy_pass'),
])->visibleWhen('auth_type', 'basic'),
```

---

## Абстрактные требования

`OptionsPage` требует только два static-метода:

```php
public static function getId(): string;     // уникальный slug страницы
public static function getTitle(): string;  // заголовок admin-панели
```

И нужно задать `$moduleId`. Остальное опционально.
