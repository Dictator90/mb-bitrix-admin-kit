# Как сделать DashboardPage

```php
use MB\Bitrix\AdminKit\Page\Standalone\DashboardPage;
use MB\Bitrix\AdminKit\Widget\CountWidget;

final class Dashboard extends DashboardPage
{
    protected function widgets(): iterable
    {
        return [
            CountWidget::make('Товары', ProductTable::class),
        ];
    }
}
```

Подробно: [Reference: Widgets](../reference/widgets.md)
