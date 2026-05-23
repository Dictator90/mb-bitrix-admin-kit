# Установка

> Для полного процесса используйте:
> - [Полный гайд для модуля](module-full-guide.md)
> - [Полный гайд для standalone](standalone-full-guide.md)

## Когда использовать

Когда подключаете пакет в новый или существующий Bitrix-проект.

## Требования

- PHP `^8.2`
- 1С-Битрикс с D7 ORM
- Composer

## Сценарий 1: внутри Bitrix-модуля

```bash
# пример: local/modules/vendor.demo
composer require mb4it/bitrix-admin-kit
```

Рекомендуемый процесс:
1. Перейдите в директорию модуля (`local/modules/<module_id>`).
2. Выполните `composer require mb4it/bitrix-admin-kit`.
3. В `include.php` модуля подключите autoload:

```php
<?php

$moduleVendor = __DIR__ . '/vendor/autoload.php';
$projectVendor = $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

if (is_file($moduleVendor)) {
    require_once $moduleVendor;
} elseif (is_file($projectVendor)) {
    require_once $projectVendor;
}
```

Важно:
- если зависимости ставите в модуль — используется `local/modules/<module_id>/vendor`;
- если зависимости ставите в корень проекта — используется `<docroot>/vendor`.

## Сценарий 2: standalone (`local/admin` + `local/php_interface`)

```bash
# пример: <docroot>/local
composer require mb4it/bitrix-admin-kit
```

Рекомендуемый процесс:
1. Установите пакет в Composer-контекст, который используете для `local`.
2. Подключите autoload в `local/php_interface/init.php`.

```php
<?php

$localVendor = $_SERVER['DOCUMENT_ROOT'] . '/local/vendor/autoload.php';
$projectVendor = $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

if (is_file($localVendor)) {
    require_once $localVendor;
} elseif (is_file($projectVendor)) {
    require_once $projectVendor;
}
```

Windows-пример для вашего сценария:
- директория проекта: `F:\Projects\mb\bitrix-test`
- установка в `F:\Projects\mb\bitrix-test\local`
- autoload: `F:\Projects\mb\bitrix-test\local\vendor\autoload.php`
- файл подключения: `F:\Projects\mb\bitrix-test\local\php_interface\init.php`

## Ограничения

- Библиотека не устанавливает и не мигрирует таблицы автоматически.
- Import UI временно отключен (см. guide по import/export).

## См. также

- [Bootstrap](bootstrap.md)
- [Первый CRUD-ресурс](first-crud-resource.md)
