# Установка

## Требования

- PHP `^8.2`
- 1С‑Битрикс с D7 ORM и доступом к административной части
- Composer

## Установка через Composer

```bash
composer require mb4it/bitrix-admin-kit
```

Пакет подтягивает зависимости `mb4it/*` и использует их через внутренние адаптеры:

- `AdminCollection`
- `AdminString`
- `AdminCondition`
- `Discovery\ClassDiscovery`

Код модуля может работать с обычными PHP-массивами, строками, `callable` и классами — адаптеры нужны внутри AdminKit.

## Bootstrap в модуле Битрикс

В `include.php` модуля подключите autoload до рендера админ-страниц:

```php
<?php

$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}
```

## Проверки в разработке

```bash
composer validate --strict
composer test
composer analyse
composer cs-check
```
