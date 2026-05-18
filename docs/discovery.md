# AdminKit scopes and discovery

AdminKit is **module-first**, but not **module-only**. Every `AdminKitManager` works inside an `AdminKitScope`.

`AdminKitScope` stores:

- `scopeId` — stable AdminKit area identifier;
- `discoveryPaths` — directories scanned for `Resource` and standalone `Page` classes.

A `scopeId` can be a Bitrix module ID, but code must not assume every scope points to an installed module. Use module IDs for module admin sections and project-level IDs such as `local.admin` or `site.admin` for local admin tools.

## `AdminKitScope::fromModuleId()`

Use `fromModuleId()` for Bitrix modules:

```php
use Bitrix\Main\Loader;
use MB\Bitrix\AdminKit\Manager\AdminKitScope;

Loader::includeModule('vendor.demo');
$scope = AdminKitScope::fromModuleId('vendor.demo');
```

`Loader::includeModule('vendor.demo')` and `fromModuleId()` are intentionally separate:

- `Loader::includeModule()` loads the module, its `include.php`, autoload and classes;
- `AdminKitScope::fromModuleId()` resolves the module directory and builds discovery paths;
- `fromModuleId()` does **not** call `Loader::includeModule()`.

By default, `fromModuleId('vendor.demo')` discovers classes in `lib/Admin` inside the module. The second argument is a relative path inside the module:

```php
$scope = AdminKitScope::fromModuleId('vendor.demo', 'lib/Resources');
```

Several module directories are supported:

```php
$scope = AdminKitScope::fromModuleId('vendor.demo', [
    'lib/Admin',
    'lib/Pages',
]);
```

The module path is resolved via Bitrix `Loader::getLocal()` when available and then through filesystem fallback: `/local/modules/<moduleId>` first, `/bitrix/modules/<moduleId>` second.

## `AdminKitScope::fromDirectory()`

Use `fromDirectory()` for code outside Bitrix modules, for example `local/classes/Admin`:

```php
use MB\Bitrix\AdminKit\Manager\AdminKitScope;

$scope = AdminKitScope::fromDirectory(
    $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
    'local.admin'
);
```

The path should be absolute in local-admin scenarios. If `scopeId` is omitted, AdminKit uses `adminkit.local`.

## `AdminKitScope::fromDirectories()`

Use `fromDirectories()` when a scope has several discovery roots:

```php
$scope = AdminKitScope::fromDirectories([
    $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
    $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Pages',
], 'local.admin');
```

## `AdminKitScope::fromModule()`

`fromModule()` accepts either a module ID string or a module-like object:

```php
$scope = AdminKitScope::fromModule('vendor.demo');
```

For strings, `fromModule()` delegates to `fromModuleId()`, so the string is treated as a Bitrix module ID and the default discovery path is `lib/Admin`.

For objects, AdminKit keeps the legacy flexible behavior and reads common methods/properties when present:

- scope ID: `getModuleId()`, `getId()`, `id()`, public `moduleId`, or public `id`;
- base path: `getPath()` or public `path`;
- module lib path: `getLibPath()` or public `libPath`.

## `AdminKitScope::fromScope()`

Use `fromScope()` when you need only a scope id without discovery paths:

```php
$scope = AdminKitScope::fromScope('site.admin');
```

This is useful for manual registration:

```php
$adminKit = AdminKit::forScope('site.admin')
    ->register(ProductResource::class)
    ->registerPage(SettingsPage::class);
```

## Adding discovery paths later

`AdminKitManager::discoverIn()` accepts variadic paths:

```php
$adminKit = AdminKit::forScope('site.admin')
    ->discoverIn(
        $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
        $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Tools'
    );
```

`discoverPaths()` accepts an array:

```php
$adminKit = AdminKit::forScope('site.admin')
    ->discoverPaths([
        $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
        $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Tools',
    ]);
```

## Missing paths behavior

`AdminKitScope` does not require paths to exist at construction time. Missing, empty, or non-directory discovery paths are ignored by the discovery layer and do not prevent manually registered resources or pages from being used.

## What is discovered

For every configured directory, the registry scans PHP classes and registers:

- non-abstract subclasses of `MB\Bitrix\AdminKit\Resource\Resource` as resources;
- non-abstract standalone page classes as pages.

Classes are keyed by their `getId()` values. Duplicate IDs are not registered twice.
