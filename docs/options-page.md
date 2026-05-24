# OptionsPage

## Что это

`OptionsPage` — standalone-страница настроек в админке Bitrix на базе `MB\Bitrix\AdminKit\Page\Standalone\OptionsPage`.

Страница рендерит форму из `fields()/components()`, сохраняет значения через Bitrix `Option` API и поддерживает single-site или multi-site режим.

## Когда использовать

- настройки модуля;
- API-ключи и секреты интеграций;
- feature flags;
- параметры поведения импорта/экспорта;
- site-specific настройки, если нужен per-site storage.

## Базовый пример

```php
<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin\Page;

use MB\Bitrix\AdminKit\Field\Password;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Page\Standalone\OptionsPage;

final class SettingsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.demo';
    protected bool $multiSite = true;

    public static function getId(): string
    {
        return 'vendor_demo_settings';
    }

    public static function getTitle(): string
    {
        return 'Настройки интеграции';
    }

    public function fields(): iterable
    {
        return [
            Text::make('API URL', 'api_url')->required(),
            Password::make('API key', 'api_key')->required(),
            Select::make('Логирование', 'log_level')
                ->options([
                    'error' => 'Только ошибки',
                    'debug' => 'Debug',
                ])
                ->default('error'),
            Switcher::make('Включить интеграцию', 'enabled')->default(true),
        ];
    }
}
```

## Поля настроек

`OptionsPage` использует те же Field-классы, что и CRUD-формы (`Text`, `Password`, `Select`, `Switcher` и т.д.).

- `fields()` — основной API;
- `components()` можно переопределить для layout-компонентов/вкладок;
- значения default берутся из Field API, если в Option storage еще нет значения.

## Сохранение значений

Фактическое поведение:

- значения читаются/пишутся через `Bitrix\Main\Config\Option`;
- storage: `b_option` (глобально) или `b_option_site` (per-site);
- ключом служит `field->getColumn()`;
- `moduleId` обязателен: если не задан в классе, страница пытается взять его из AdminKit context; если не найден — рендерится ошибка.

## Чтение значений

Пользовательский код обычно читает значения напрямую через Bitrix Option API:

```php
<?php

use Bitrix\Main\Config\Option;

$apiUrl = Option::get('vendor.demo', 'api_url', '');
$enabled = Option::get('vendor.demo', 'enabled', 'N') === 'Y';
```

Для multi-site чтения укажите `$siteId` четвертым аргументом `Option::get()`.

## Многосайтовость

Поддерживается.

- Установите `protected bool $multiSite = true;`.
- Страница рендерит формы/вкладки по сайтам.
- Значения хранятся отдельно для каждого `SITE_ID`.

Если `multiSite = false`, используется общий storage без site-specific сегментации.

## Валидация

`OptionsPage` использует Field validation и обработчик формы страницы.

Практически это означает:

- fluent-методы полей (`required()`, `maxLength()`, и т.д.) участвуют в проверке;
- сохранение проходит через page post handler;
- ошибки отображаются на странице.

## Практические сценарии

- страница настроек интеграционного модуля;
- включить/выключить feature через `Switcher`;
- хранить API token/secret через `Password`;
- разделять значения по сайтам через `multiSite`.

## Связанные разделы

- [Pages](pages.md)
- [Fields](fields.md)
- [Forms lifecycle](user/guides/forms-lifecycle.md)
- [Permissions](user/guides/permissions.md)
- [Cookbook: OptionsPage](user/cookbook/options-page.md)
