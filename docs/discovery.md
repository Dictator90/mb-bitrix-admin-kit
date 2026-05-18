# AdminKit scopes and discovery

AdminKit is **module-first**, but not **module-only**. Every `AdminKitManager` works inside an `AdminKitScope`; the scope is identified by a `scopeId`.

## `scopeId`

`scopeId` is a stable unique identifier for an AdminKit area. It can be a real Bitrix module ID, but it does not have to point to an installed module.

Examples:

- `vendor.module`
- `site.admin`
- `catalog.admin`
- `content.tools`
- `local.interface`

Use module IDs for module admin sections, and project-level names for resources stored in `local/php_interface` or custom directories.

## `AdminKitScope`

`MB\Bitrix\AdminKit\Manager\AdminKitScope` stores:

- `scopeId` — the AdminKit area identifier;
- `basePath` — optional base path for the scope;
- `discoveryPaths` — directories scanned for resources and pages.

The scope object does not require paths to exist. Path validation is handled by discovery configuration and the registry, so a missing optional directory does not break admin pages.

## Module-first setup: `forModule()`

For a module ID string, pass the module ID and add a lib path explicitly:

```php
use MB\Bitrix\AdminKit\AdminKit;

$adminKit = AdminKit::forModule('vendor.module')
    ->discoverIn('/local/modules/vendor.module/lib');
```

For a module object, pass the object directly:

```php
$adminKit = AdminKit::forModule($moduleObject);
```

AdminKit intentionally does not depend on a concrete module contract. It reads module data through common methods/properties when present:

- scope ID: `getModuleId()`, `getId()`, `id()`, public `moduleId`, or public `id`;
- base path: `getPath()` or public `path`;
- module lib path: `getLibPath()` or public `libPath`.

If `getLibPath()` or `libPath` is available, the path is added to discovery automatically.

## Scope-only setup: `forScope()`

Use `forScope()` for admin tools that are not bound to a Bitrix module:

```php
$adminKit = AdminKit::forScope('site.admin')
    ->discoverIn('/local/php_interface/lib/Admin');
```

This is the recommended setup for `local/php_interface` resources.

## Directory shortcuts

Use `fromDirectory()` when one directory is the whole scope:

```php
$adminKit = AdminKit::fromDirectory(
    '/local/php_interface/lib/Admin',
    scopeId: 'site.admin'
);
```

Use `fromDirectories()` for several discovery roots:

```php
$adminKit = AdminKit::fromDirectories([
    '/local/php_interface/lib/Admin',
    '/local/php_interface/lib/Tools',
], scopeId: 'site.admin');
```

If `scopeId` is omitted for directory shortcuts, AdminKit uses `adminkit.local`.

## Adding discovery paths later

`discoverIn()` accepts variadic paths:

```php
$adminKit = AdminKit::forScope('site.admin')
    ->discoverIn(
        '/local/php_interface/lib/Admin',
        '/local/php_interface/lib/Tools'
    );
```

`discoverPaths()` accepts an array:

```php
$adminKit = AdminKit::forScope('site.admin')
    ->discoverPaths([
        '/local/php_interface/lib/Admin',
        '/local/php_interface/lib/Tools',
    ]);
```

Duplicate paths are ignored after normalization.

## Manual registration

Discovery is optional. Resources and pages can be registered manually:

```php
$adminKit = AdminKit::forScope('site.admin')
    ->register(ProductResource::class)
    ->registerPage(SettingsPage::class);
```

Manual registration works with empty discovery paths and with missing discovery directories.

## What is discovered

For every configured directory, the registry scans PHP classes and registers:

- non-abstract subclasses of `MB\Bitrix\AdminKit\Resource\Resource` as resources;
- non-abstract subclasses of `MB\Bitrix\AdminKit\Pages\AbstractPage` as pages.

Classes are keyed by their `getId()` values. Duplicate IDs are not registered twice.

## Missing paths behavior

Missing, empty, or non-directory paths are ignored. They do not throw fatal errors and do not prevent manually registered resources or pages from being used.
