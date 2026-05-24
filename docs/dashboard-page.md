# DashboardPage

## Что это

`DashboardPage` — standalone-страница для сводной информации, виджетов и быстрых действий.

Класс `MB\Bitrix\AdminKit\Page\Standalone\DashboardPage` наследует `CustomPage` и рендерит контент через `DashboardRenderer` на основе `widgets()`.

## Когда использовать

- counters/статистика;
- health/status блоки;
- quick links в разделы админки;
- обзорные блоки импорта/экспорта/очередей;
- диагностические карточки.

## Базовый пример

```php
<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin\Page;

use MB\Bitrix\AdminKit\Component\Alert;
use MB\Bitrix\AdminKit\Page\Standalone\DashboardPage;
use MB\Bitrix\AdminKit\Widget\CountWidget;
use Vendor\Demo\Orm\OrderTable;
use Vendor\Demo\Orm\ProductTable;

final class DemoDashboardPage extends DashboardPage
{
    public static function getId(): string
    {
        return 'vendor_demo_dashboard';
    }

    public static function getTitle(): string
    {
        return 'Панель модуля';
    }

    protected function widgets(): iterable
    {
        return [
            CountWidget::make('Товары', ProductTable::class),
            CountWidget::make('Заказы', OrderTable::class)->color('warning'),
            Alert::make('Импорт UI временно отключен. Используйте service layer.', Alert::WARNING),
        ];
    }
}
```

## Widgets / blocks

Widget API есть и используется.

Обычно применяются классы из `MB\Bitrix\AdminKit\Widget\*` и layout/UI-компоненты.

- в `widgets()` можно вернуть widget, `ComponentContract` или строку HTML;
- `DashboardRenderer` собирает их в сетку dashboard;
- для кастомного виджета лучше расширять существующий Widget-класс, а не писать тяжелый HTML inline.

## Bitrix UI

Рекомендации:

- использовать Bitrix-native компоненты/стили и расширения;
- выносить сложную клиентскую логику в Bitrix extension;
- не перегружать страницу большими heredoc-блоками HTML/JS в PHP.

## Практические сценарии

- dashboard со счетчиками сущностей;
- dashboard со статусом интеграций;
- dashboard с quick links и alert-блоками;
- обзорная страница с экспортными ограничениями/подсказками.

## Связанные разделы

- [Pages](pages.md)
- [Widgets reference](user/reference/widgets.md)
- [UI integration](user/guides/ui-integration.md)
- [Performance & diagnostics](user/guides/performance-diagnostics.md)
- [Cookbook: Dashboard](user/cookbook/dashboard.md)
