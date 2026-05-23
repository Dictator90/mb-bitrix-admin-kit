# SidePanel

Класс: `MB\Bitrix\AdminKit\Component\SidePanel`.

Назначение: helper для JS-открытия/закрытия Bitrix SidePanel и перезагрузки грида.

Методы:
- `open(string $url, array $options = [])`
- `close()`
- `reload()`
- `notifyParentGrid(string $gridId)`
- `closeOnSaved()`

Пример:
```php
$js = SidePanel::open('/bitrix/admin/demo.php?action=edit&id=10', [
    'width' => 1200,
    'reloadGridId' => 'PRODUCT_GRID',
]);
```
