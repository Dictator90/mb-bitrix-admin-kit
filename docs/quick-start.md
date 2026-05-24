# Quick Start

1. Установите пакет: [Installation](installation.md).
2. Выберите сценарий:
   - [Внутри модуля](user/guides/module-integration.md)
   - [Вне модуля](user/guides/standalone-integration.md)
3. Создайте `ProductResource` (пример: [First CRUD](user/cookbook/first-crud.md)).
4. Создайте admin-файл (`/bitrix/admin/*.php` или `/local/admin/*.php`) и вызывайте:

```php
AdminKit::forModule('vendor.demo')->getCurrentPage()->render();
// или
AdminKit::fromDirectory($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/lib/DemoAdmin', 'demo.admin')->getCurrentPage()->render();
```

5. Добавьте пункт меню (см. [Admin menu and pages](user/guides/admin-menu-and-pages.md)).
6. Откройте URL admin-файла и проверьте рендер index/options страниц.
