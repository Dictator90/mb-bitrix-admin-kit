# Scope AdminKit и discovery

AdminKit ориентирован на **модули**, но не ограничен **только модулями**. Каждый `AdminKitManager` работает внутри `AdminKitScope`.

`AdminKitScope` хранит:

- `scopeId` — стабильный идентификатор области AdminKit;
- `discoveryPaths` — каталоги, в которых ищутся классы `Resource` и standalone `Page`.

`scopeId` может быть ID модуля Bitrix, но код не должен предполагать, что каждый scope указывает на установленный модуль. Используйте ID модулей для admin-разделов модулей и project-level ID вроде `local.admin` или `site.admin` для локальных admin-инструментов.

## `AdminKitScope::fromModuleId()`

Используйте `fromModuleId()` для модулей Bitrix:

```php
use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\Manager\AdminKitScope;

Loader::includeModule('vendor.demo');
$scope = AdminKitScope::fromModuleId('vendor.demo');
```

`Loader::includeModule('vendor.demo')` и `fromModuleId()` намеренно разделены:

- `Loader::includeModule()` загружает модуль, его `include.php`, autoload и классы;
- `AdminKitScope::fromModuleId()` разрешает каталог модуля и строит discovery paths;
- `fromModuleId()` **не** вызывает `Loader::includeModule()`.

По умолчанию `fromModuleId('vendor.demo')` ищет классы в `lib/Admin` внутри модуля. Второй аргумент — относительный путь внутри модуля:

```php
$scope = AdminKitScope::fromModuleId('vendor.demo', 'lib/Resources');
```

Поддерживается несколько каталогов модуля:

```php
$scope = AdminKitScope::fromModuleId('vendor.demo', [
    'lib/Admin',
    'lib/Pages',
]);
```

Путь модуля разрешается через Bitrix `Loader::getLocal()` при наличии, затем через fallback по файловой системе: сначала `/local/modules/<moduleId>`, затем `/bitrix/modules/<moduleId>`.

## `AdminKitScope::fromDirectory()`

Используйте `fromDirectory()` для кода вне модулей Bitrix, например `local/classes/Admin`:

```php
use MB\Bitrix\AdminKit\Manager\AdminKitScope;

$scope = AdminKitScope::fromDirectory(
    $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
    'local.admin'
);
```

В сценариях local-admin путь должен быть абсолютным. Если `scopeId` не указан, AdminKit использует `adminkit.local`.

## `AdminKitScope::fromDirectories()`

Используйте `fromDirectories()`, когда у scope несколько корней discovery:

```php
$scope = AdminKitScope::fromDirectories([
    $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
    $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Pages',
], 'local.admin');
```

## `AdminKitScope::fromModule()`

`fromModule()` принимает строку ID модуля или module-like объект:

```php
$scope = AdminKitScope::fromModule('vendor.demo');
```

Для строк `fromModule()` делегирует в `fromModuleId()`, то есть строка трактуется как ID модуля Bitrix с путём discovery по умолчанию `lib/Admin`.

Для объектов AdminKit сохраняет гибкое legacy-поведение и читает общие методы/свойства при наличии:

- scope ID: `getModuleId()`, `getId()`, `id()`, публичное `moduleId` или публичное `id`;
- базовый путь: `getPath()` или публичное `path`;
- lib модуля: `getLibPath()` или публичное `libPath`.

## `AdminKitScope::fromScope()`

Используйте `fromScope()`, когда нужен только scope id без discovery paths:

```php
$scope = AdminKitScope::fromScope('site.admin');
```

Удобно для ручной регистрации:

```php
$adminKit = AdminKit::forScope('site.admin')
    ->register(ProductResource::class)
    ->registerPage(SettingsPage::class);
```

## Добавление discovery paths позже

`AdminKitManager::discoverIn()` принимает variadic paths:

```php
$adminKit = AdminKit::forScope('site.admin')
    ->discoverIn(
        $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
        $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Tools'
    );
```

`discoverPaths()` принимает массив:

```php
$adminKit = AdminKit::forScope('site.admin')
    ->discoverPaths([
        $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
        $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Tools',
    ]);
```

## Поведение при отсутствующих путях

`AdminKitScope` не требует существования путей на этапе конструирования. Отсутствующие, пустые или не-каталожные discovery paths игнорируются слоем discovery и не мешают использовать вручную зарегистрированные ресурсы или страницы.

## Что обнаруживается

Для каждого настроенного каталога registry сканирует PHP-классы и регистрирует:

- неабстрактные подклассы `MB\Bitrix\AdminKit\Resource\Resource` как ресурсы;
- неабстрактные standalone page classes как страницы.

Ключи классов — значения `getId()`. Дубликаты ID не регистрируются повторно.
