# Installation

## Варианты установки

| Сценарий | Когда использовать | Где читать |
|---|---|---|
| Внутри Bitrix-модуля | Собственный модуль/маркетплейс | [Module integration](user/guides/module-integration.md) |
| Вне модуля | Проектная админка без отдельного модуля | [Standalone integration](user/guides/standalone-integration.md) |

## Composer

```bash
composer require mb4it/bitrix-admin-kit
```

## Vendor/autoload

Пакет не заработает без подключенного Composer autoload.

- В модуле: обычно `local/modules/<module_id>/include.php` (если vendor внутри модуля).
- Вне модуля: обычно `local/php_interface/init.php`.

## Проверка установки

```php
var_dump(class_exists(\MB\Bitrix\AdminKit\Resource\DataManagerResource::class));
```

## Что дальше

- [Module integration](user/guides/module-integration.md)
- [Standalone integration](user/guides/standalone-integration.md)
- [Quick Start](quick-start.md)
- [First CRUD cookbook](user/cookbook/first-crud.md)
- [OptionsPage cookbook](user/cookbook/options-page.md)
