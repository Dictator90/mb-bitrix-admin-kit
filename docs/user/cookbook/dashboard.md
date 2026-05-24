# Рецепт: стартовый DashboardPage

## Задача

Собрать dashboard с counters и служебным уведомлением.

## Решение

```php
<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin\Page;

use MB\Bitrix\AdminKit\Component\Alert;
use MB\Bitrix\AdminKit\Page\Standalone\DashboardPage;
use MB\Bitrix\AdminKit\Widget\CountWidget;
use Vendor\Demo\Orm\ProductTable;

final class ModuleDashboardPage extends DashboardPage
{
    public static function getId(): string
    {
        return 'vendor_demo_dashboard';
    }

    public static function getTitle(): string
    {
        return 'Dashboard';
    }

    protected function widgets(): iterable
    {
        return [
            CountWidget::make('Товары', ProductTable::class),
            Alert::make('Импорт UI сейчас отключен.', Alert::WARNING),
        ];
    }
}
```

## Важные замечания

- `DashboardPage` рендерит содержимое через `widgets()`;
- используйте существующие Widget/Component классы вместо тяжелого HTML;
- сложную логику считайте в сервисах, а не в page-классе.

## Ссылки

- [DashboardPage](../../dashboard-page.md)
- [Widgets reference](../reference/widgets.md)
