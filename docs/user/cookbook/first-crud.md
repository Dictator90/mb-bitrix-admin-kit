# Первый CRUD Resource (end-to-end)

## Структура

```text
local/modules/vendor.demo/
  lib/Admin/ProductResource.php
  admin/vendor_demo_admin.php
  admin/menu.php
```

## ProductResource

```php
final class ProductResource extends DataManagerResource
{
    public function dataManagerClass(): string
    {
        return ProductTable::class;
    }
}
```

Важно: `dataManagerClass()` — **не static**.

## Admin page file

Создайте `admin/vendor_demo_admin.php` и рендерите:

```php
AdminKit::forModule('vendor.demo')->getCurrentPage()->render();
```

(с Bitrix prolog/epilog, как в guide).

## Menu item

В `admin/menu.php` используйте:

```php
'items' => AdminKit::forModule('vendor.demo')->getMenu('/bitrix/admin/vendor_demo_admin.php')
```

## Результат

Откройте `/bitrix/admin/vendor_demo_admin.php?lang=ru`.

## Типовые ошибки

- Не подключен autoload.
- Нет `Loader::includeModule('vendor.demo')`.
- Ошибка сигнатуры `dataManagerClass()`.
- Несовпадение URL в меню и admin-файла.
