# OptionsPage

## Задача

Сделать страницу настроек модуля с сохранением в Bitrix options storage.

## Когда использовать

Когда нужны admin-настройки (API key, режимы работы, флаги).

## Решение

Наследуйте страницу от `OptionsPage`, задайте `moduleId` и верните поля из `fields()`. Компонент сохраняет значения через `Bitrix\Main\Config\Option`.

## Полный пример

```php
use MB\Bitrix\AdminKit\Field\Password;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Page\Standalone\OptionsPage;

final class ModuleOptionsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.module';

    public static function title(): string
    {
        return 'Module options';
    }

    public function fields(): iterable
    {
        return [
            Text::make('API URL', 'api_url')->required(),
            Password::make('API token', 'api_token'),
            Switcher::make('Debug mode', 'debug_mode'),
            Select::make('Log level', 'log_level')->options(['error' => 'Error', 'info' => 'Info']),
        ];
    }
}
```

## Как это работает

`OptionsPage` сам рендерит форму, проверяет `sessid` и сохраняет значения в `b_option` (или `b_option_site` при multisite-режиме).

## Что важно учесть

- `moduleId` должен быть задан явно.
- Не храните незашифрованные секреты, если есть требования ИБ.
- Site-specific режим включайте только если он нужен и протестирован.

## Связанные разделы

- [Options Page](../../options-page.md)
- [Reference: Pages](../reference/pages.md)
