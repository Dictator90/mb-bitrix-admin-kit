# MB Bitrix Admin Kit

`mb4it/bitrix-admin-kit` — библиотека для декларативного построения административных CRUD-разделов и страниц настроек в 1С-Битрикс на D7 ORM. Она позволяет разработчикам описывать интерфейсы (ресурсы, поля, фильтры, действия, страницы) в виде чистых PHP-классов.

---

## 1. Требования

* PHP `^8.2`
* Установленный 1С-Битрикс с поддержкой D7 ORM
* Composer для управления зависимостями

---

## 2. Установка

Установите пакет через Composer:

```bash
composer require mb4it/bitrix-admin-kit
```

---

## 3. Быстрый старт: Страница настроек (OptionsPage)

Страница настроек сохраняет значения полей в системную таблицу настроек модуля (b_option) через стандартный `Bitrix\Main\Config\Option`.

```php
<?php

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Page\Standalone\OptionsPage;
use MB\Bitrix\AdminKit\Field\Text;

final class SettingsPage extends OptionsPage
{
    public static function getId(): string
    {
        return 'settings_page';
    }

    public static function getTitle(): string
    {
        return 'Настройки модуля';
    }

    public function components(): iterable
    {
        return [
            Text::make('Название компании', 'company_name'),
            Text::make('Email для уведомлений', 'admin_email'),
        ];
    }
}
```

---

## 4. Быстрый старт: CRUD Ресурс (DataManagerResource)

Для создания полноценного CRUD-интерфейса (список, создание, редактирование, удаление записей) на базе таблицы Bitrix D7 ORM, наследуйте ваш ресурс от `DataManagerResource`.

```php
<?php

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Field\ID;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Resource\DataManagerResource;
use Vendor\Demo\Orm\ProductTable;

final class ProductResource extends DataManagerResource
{
    public static function getId(): string
    {
        return 'products';
    }

    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }

    public function getTitle(): string
    {
        return 'Товары';
    }

    public function fields(): array
    {
        return [
            ID::make('ID'),
            Text::make('Название', 'NAME')->required(),
            Text::make('Артикул', 'CODE'),
        ];
    }
}
```

---

## 5. Использование в Битрикс-модуле

При использовании внутри стандартного Bitrix-модуля (например, `vendor.demo`):

1. В файле `include.php` вашего модуля подключите `vendor/autoload.php` пакета.
2. В файле админки (например, `/bitrix/admin/vendor_demo_admin.php`) выполните инициализацию:

```php
<?php

use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\Manager\AdminKitManager;
use MB\Bitrix\AdminKit\Manager\AdminKitScope;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loader::includeModule('vendor.demo');

// Scope автоматически найдет ресурсы и страницы в директории lib/Admin/
$scope = AdminKitScope::fromModuleId('vendor.demo');
(new AdminKitManager($scope))->getCurrentPage()->render();

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

---

## 6. Использование вне модуля (Standalone)

Если вы хотите использовать пакет без создания отдельного Битрикс-модуля (например, для локальных скриптов):

1. Подключите `vendor/autoload.php` в `local/php_interface/init.php` или в конкретном скрипте.
2. Создайте Scope, указав путь к директории с вашими классами настроек и ресурсов:

```php
<?php

use MB\Bitrix\AdminKit\Manager\AdminKitManager;
use MB\Bitrix\AdminKit\Manager\AdminKitScope;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

$scope = AdminKitScope::fromDirectory(
    $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
    'local.admin'
);

(new AdminKitManager($scope))->getCurrentPage()->render();

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

---

## 7. Основные концепции

* **Ресурсы (Resources)** — описывают сущности системы. Для ORM-таблиц используется `DataManagerResource`.
* **Поля (Fields)** — декларативные элементы интерфейса (Text, ID, Select, BelongsToMany и др.), управляющие отображением и сохранением.
* **Действия (Actions)** — кастомные операции над строками (Row Actions) или групповые операции (Bulk Actions) со встроенными валидациями прав и CSRF.
* **Страницы (Pages)** — контроллеры страниц в админке. `IndexPage` отвечает за грид, `FormPage` — за форму редактирования, `DetailPage` — за детальный просмотр.

---

## 8. Ограничения

* Импорт UI на индексных страницах временно отключен (доступен только на уровне сервиса/библиотеки).
* Поддерживается только CSV-экспорт (`CsvExporter`).
* Сохранение ORM-ресурсов всегда происходит через Bitrix `EntityObject` (`$entityObject->save()`). Сохранение в виде сырых массивов для ORM не поддерживается.
* Связи `BelongsToMany` синхронизируются через стратегии (`ManualPivotSyncStrategy` и `OrmMutationSyncStrategy`).

---

## 9. Навигация по документации

* Установка: [docs/installation.md](docs/installation.md)
* Быстрый старт: [docs/quick-start.md](docs/quick-start.md)
* Архитектура: [docs/architecture.md](docs/architecture.md)
* Scope и discovery: [docs/discovery.md](docs/discovery.md)
* Ресурсы и страницы: [docs/resources.md](docs/resources.md), [docs/pages.md](docs/pages.md)
* Грид (Списки): [docs/grid.md](docs/grid.md)
* Поля и фильтры: [docs/fields.md](docs/fields.md), [docs/filters.md](docs/filters.md), [docs/relations.md](docs/relations.md)
* Действия: [docs/actions.md](docs/actions.md), [docs/bulk-actions.md](docs/bulk-actions.md)
* Экспорт/импорт: [docs/import-export.md](docs/import-export.md)
* Совместимость: [docs/backward-compatibility.md](docs/backward-compatibility.md)
* Тестирование: [docs/testing.md](docs/testing.md)

---

## 10. Разработка пакета

Для тестирования и проверки качества кода пакета локально:

```bash
# Установка зависимостей
composer install

# Запуск тестов (Unit + Integration)
composer test

# Запуск статического анализа (PHPStan)
composer analyse

# Проверка стиля кода (PHP CS Fixer)
composer cs-check

# Автоматическое исправление стиля кода
composer cs-fix
```
