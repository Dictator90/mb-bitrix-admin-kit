# Installation

## Что это

Пошаговая установка `mb4it/bitrix-admin-kit` и минимальное подключение в Bitrix-проекте.

## Когда использовать

- Вы подключаете пакет впервые.
- Нужен понятный bootstrap для модуля или standalone-сценария.

## 1) Установка через Composer

```bash
composer require mb4it/bitrix-admin-kit
```

Требования: PHP `^8.2`, Composer, 1C-Битрикс с D7 ORM.

## 2) Подключение внутри Bitrix-модуля

### Когда нужен `Loader::includeModule()`

`Loader::includeModule('<module_id>')` нужен, когда вы используете классы **самого модуля** (например, ваш `DataManager`) и хотите гарантировать загрузку module bootstrap.

### Пример bootstrap в модуле

```php
<?php

declare(strict_types=1);

use Bitrix\Main\Loader;

require_once __DIR__ . '/vendor/autoload.php';

Loader::includeModule('vendor.demo');
```

### Создание менеджера Admin Kit для module scope

```php
<?php

declare(strict_types=1);

use MB\Bitrix\AdminKit\AdminKit;

$adminKit = AdminKit::forModule('vendor.demo');
```

`AdminKit::forModule()` принимает строковый module id или объект модуля.

## 3) Подключение вне Bitrix-модуля (standalone)

Если вы не завязаны на module id, используйте directory scope.

```php
<?php

declare(strict_types=1);

use MB\Bitrix\AdminKit\AdminKit;

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/vendor/autoload.php';

$adminKit = AdminKit::fromDirectory(
    $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/admin-kit',
    'local.admin'
);
```

- Первый аргумент — абсолютный путь к директории discovery/регистрации.
- Второй аргумент (`scopeId`) опционален; используйте его, если хотите фиксированный ID области.

## 4) Проверка подключения

Минимальная проверка: создать менеджер и вызвать рендер.

```php
<?php

declare(strict_types=1);

use MB\Bitrix\AdminKit\AdminKit;

$adminKit = AdminKit::forScope('demo.scope');

if ($adminKit instanceof \MB\Bitrix\AdminKit\Manager\AdminKitManager) {
    echo 'Admin Kit connected';
}
```

## 5) Что читать дальше

- [Quick Start](quick-start.md)
- [Resources](resources.md)
- [Pages](pages.md)
- [Fields](fields.md)
- [Grid](grid.md)
