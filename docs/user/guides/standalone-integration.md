# Использование вне Bitrix-модуля

## Что соберем

CRUD + OptionsPage без собственного модуля, через `local/php_interface` и `local/admin`.

## Структура

```text
local/php_interface/
  init.php
  lib/DemoAdmin/
    ProductResource.php
    SettingsPage.php
local/admin/
  demo_admin.php
```

## 1) Composer и autoload

Подключите пакет в composer проекта (или `local/`, если у вас отдельный composer-контур).

`local/php_interface/init.php`:

```php
<?php

declare(strict_types=1);

$autoload = $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
if (!is_file($autoload)) {
    $autoload = $_SERVER['DOCUMENT_ROOT'] . '/local/vendor/autoload.php';
}

if (is_file($autoload)) {
    require_once $autoload;
}
```

## 2) PSR-4 для классов

```json
{
  "autoload": {
    "psr-4": {
      "DemoAdmin\\": "local/php_interface/lib/DemoAdmin/"
    }
  }
}
```

## 3) Resource

Класс ресурса такой же, как в module-сценарии: `extends DataManagerResource` и `public function dataManagerClass(): string`.

## 4) OptionsPage вне модуля

Поддерживается. Если scope не модульный, `OptionsPage` сохраняет опции через `main` (см. `AdminKitScope::optionModuleId()`), поэтому нужен стабильный `scopeId`, например `demo.admin`.

## 5) Admin-файл

`local/admin/demo_admin.php`:

```php
<?php

declare(strict_types=1);

use MB\Bitrix\AdminKit\AdminKit;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

AdminKit::fromDirectory(
    $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/lib/DemoAdmin',
    'demo.admin'
)->getCurrentPage()->render();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

## 6) Меню вне модуля

Через `OnBuildGlobalMenu` в `local/php_interface/init.php`.

```php
use Bitrix\Main\EventManager;
use MB\Bitrix\AdminKit\AdminKit;

EventManager::getInstance()->addEventHandler('main', 'OnBuildGlobalMenu', static function (&$aGlobalMenu, &$aModuleMenu): void {
    $aModuleMenu[] = [
        'parent_menu' => 'global_menu_services',
        'section' => 'demo_admin',
        'sort' => 1000,
        'text' => 'Demo admin',
        'title' => 'Demo admin',
        'items_id' => 'menu_demo_admin',
        'items' => AdminKit::fromDirectory($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/lib/DemoAdmin', 'demo.admin')
            ->getMenu('/local/admin/demo_admin.php'),
    ];
});
```

## Ограничения

- Нет lifecycle install/uninstall модуля.
- Нет `Loader::includeModule()` для «своего» module id.
- Для поставляемых решений предпочтительнее module-сценарий.
