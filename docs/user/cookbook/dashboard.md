# DashboardPage

## Задача

Сделать dashboard с ключевыми метриками и виджетами.

## Когда использовать

Для обзорной страницы модуля: счетчики, графики, статусы интеграций.

## Решение

Наследуйте страницу от `DashboardPage` и переопределите `widgets()`. Возвращайте виджеты (`CountWidget`, `GraphWidget`) или layout-компоненты.

## Полный пример

```php
use MB\Bitrix\AdminKit\Page\Standalone\DashboardPage;
use MB\Bitrix\AdminKit\Widget\CountWidget;
use MB\Bitrix\AdminKit\Widget\GraphWidget;

final class ModuleDashboardPage extends DashboardPage
{
    protected static function title(): string
    {
        return 'Dashboard';
    }

    protected function widgets(): iterable
    {
        return [
            CountWidget::make('Orders', \Vendor\Module\Internals\OrderTable::class),
            GraphWidget::make('Sales', 'line')->span(12)->data([]),
        ];
    }
}
```

## Как это работает

`DashboardPage` делегирует рендер в `DashboardRenderer`; UI остается Bitrix-native и совместим с layout-компонентами пакета.

## Что важно учесть

- Тяжелые SQL-агрегации кэшируйте.
- Виджеты должны быть только read-only (без опасных side effects).

## Связанные разделы

- [Dashboard Page](../../dashboard-page.md)
- [Reference: Widgets](../reference/widgets.md)
- [Reference: Pages](../reference/pages.md)
