# Discovery и routing

## Когда использовать

Когда настраиваете scope, сканирование классов, URL и маршруты admin-страниц.

## Минимальный пример

```php
use MB\Bitrix\AdminKit\AdminKit;

$adminKit = AdminKit::forScope('site.admin')
    ->discoverIn(
        $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Admin',
        $_SERVER['DOCUMENT_ROOT'] . '/local/classes/Pages'
    );
```

Основные точки входа:
- `AdminKit::forModule()`
- `AdminKit::forScope()`
- `AdminKit::fromDirectory()`
- `AdminKit::fromDirectories()`

## Ограничения

- `AdminKitScope::fromModuleId()` только строит discovery paths и не вызывает `Loader::includeModule()`.
- Отсутствующие discovery-директории не должны ломать вручную зарегистрированные ресурсы/страницы.

## См. также

- [Bootstrap](../getting-started/bootstrap.md)
- [Reference: UrlGenerator](../reference/url-generator.md)
