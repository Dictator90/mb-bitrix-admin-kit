# SidePanel, меню и toolbar

## Когда использовать

Когда нужно связать CRUD/standalone-страницы с UX Bitrix UI.

## Минимальный пример

```php
public function useSidePanel(): bool
{
    return true;
}

public function createInSidePanel(): bool
{
    return true;
}
```

Меню:

```php
$items = \MB\Bitrix\AdminKit\AdminKit::forModule('vendor.demo')
    ->getMenu('/bitrix/admin/demo_admin.php');
```

Toolbar для standalone:

```php
use MB\Bitrix\AdminKit\Manager\ToolbarAction;

protected function toolbarActions(): iterable
{
    return [
        ToolbarAction::make('Обновить')->href($this->url(['refresh' => '1'])),
    ];
}
```

## Ограничения

- Для нового кода подключайте ассеты централизованно через `AssetManager`.
- SidePanel поведение должно сохранять full-page режим при отсутствии `IFRAME=Y`.

## См. также

- [Reference: Pages](../reference/pages.md)
- [Reference: Components](../reference/components/README.md)
