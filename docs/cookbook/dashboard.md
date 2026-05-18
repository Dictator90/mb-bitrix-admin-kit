# Как сделать dashboard

## Минимальный пример

```php
<?php

namespace Vendor\Module\Admin;

use MB\Bitrix\AdminKit\Pages\DashboardPage;
use MB\Bitrix\AdminKit\Widget\CountWidget;

final class MyDashboard extends DashboardPage
{
    public static function getId(): string    { return 'dashboard'; }
    public static function getTitle(): string { return 'Dashboard'; }

    protected function widgets(): iterable
    {
        return [
            CountWidget::make('Товары',  ProductTable::class),
            CountWidget::make('Заказы',  OrderTable::class),
            CountWidget::make('Выручка', OrderTable::class)
                ->value(fn() => '₽ ' . number_format(OrderRepo::revenue(), 0, '.', ' ')),
        ];
    }
}
```

---

## Статистические карточки (CountWidget)

```php
use MB\Bitrix\AdminKit\Widget\CountWidget;

CountWidget::make('Товары', ProductTable::class)

CountWidget::make('Новые заказы', OrderTable::class)
    ->filter(['STATUS' => 'new'])               // ORM-фильтр для getCount()
    ->color('warning')                          // 'primary' | 'success' | 'danger' | 'warning'
    ->icon('--cart')                            // ui-icon-set CSS класс
    ->href('/bitrix/admin/vendor_orders.php')   // сделать карточку ссылкой
    ->span(3)                                   // ширина в 12-колоночной сетке (по умолчанию 3)

// Произвольное значение вместо ORM count
CountWidget::make('Выручка', OrderTable::class)
    ->value(fn() => '₽ ' . number_format(OrderRepo::totalRevenue(), 0, '.', ' '))
    ->color('success')
```

По умолчанию каждая карточка занимает **3 колонки из 12** → четыре карточки в ряд.

---

## График (ChartWidget / GraphWidget)

`ChartWidget` — основной виджет графика. `GraphWidget` сохранён как alias.
Инициализация графика выполняется через локальное расширение `mb.admin.kit` без CDN-скриптов из PHP.

```php
use MB\Bitrix\AdminKit\Widget\ChartWidget;

// Вертикальные столбцы (по умолчанию)
ChartWidget::make('Заказы по месяцам')
    ->span(12)
    ->data([
        ['category' => 'Янв', 'value' => 42],
        ['category' => 'Фев', 'value' => 67],
        ['category' => 'Мар', 'value' => 55],
    ])
    ->height(280)

// Горизонтальные столбцы — удобно для топ-N
ChartWidget::make('Топ модулей', 'bar')
    ->horizontal()
    ->categoryField('module')   // поле строк, которое идёт в labels
    ->valueField('count')       // поле строк с числовым значением
    ->dataCallback(fn() => MyRepo::topModules(10))
    ->span(12)
    ->height(300)

// Круговая диаграмма
ChartWidget::make('По статусам', 'pie')
    ->span(6)
    ->categoryField('title')    // поле для подписей сегментов
    ->data([
        ['title' => 'Новые',   'value' => 12],
        ['title' => 'Готовые', 'value' => 38],
        ['title' => 'Отмена',  'value' => 5],
    ])

// Линейный график с данными из базы
ChartWidget::make('Регистрации по дням', 'line')
    ->span(12)
    ->categoryField('date')
    ->valueField('cnt')
    ->dataCallback(fn() => UserRepo::registrationsPerDay(30))
    ->height(250)
```

Ключи строк по умолчанию — `category` и `value`. Если репозиторий возвращает другие имена, переопределите через `->categoryField()` / `->valueField()`.

---

## Полная компоновка с Layout-компонентами

`widgets()` принимает любые `ComponentContract` — `Grid`, `Column`, `Box`, `Alert`, `Tabs` и т.д.:

```php
use MB\Bitrix\AdminKit\Component\Alert;
use MB\Bitrix\AdminKit\Component\Layout\Box;
use MB\Bitrix\AdminKit\Component\Layout\Column;
use MB\Bitrix\AdminKit\Component\Layout\Grid;
use MB\Bitrix\AdminKit\Widget\CountWidget;
use MB\Bitrix\AdminKit\Widget\GraphWidget;

protected function widgets(): iterable
{
    return [
        // Ряд из четырёх stat-card'ов (3+3+3+3 = 12)
        CountWidget::make('Товары',       ProductTable::class)->color('primary'),
        CountWidget::make('Заказы',       OrderTable::class)->color('warning'),
        CountWidget::make('Пользователи', UserTable::class)->color('success'),
        CountWidget::make('Выручка',      OrderTable::class)
            ->value(fn() => '₽ ' . number_format(OrderRepo::revenue(), 0, '.', ' ')),

        // График на всю ширину
        GraphWidget::make('Заказы по месяцам')
            ->span(12)
            ->dataCallback(fn() => OrderRepo::byMonth()),

        // Grid-компонент с произвольным содержимым на всю ширину
        Grid::make([
            Column::make([
                CountWidget::make('Ожидают', OrderTable::class)
                    ->filter(['STATUS' => 'pending']),
            ])->span(4),
            Column::make([
                Alert::make('Синхронизация прошла успешно', Alert::SUCCESS),
            ])->span(8),
        ])->style('grid-column', 'span 12'),
    ];
}
```

> Для layout-компонентов используйте `->style('grid-column', 'span 12')` чтобы растянуть на всю ширину сетки дашборда.

---

## HTML-ссылки в Alert

По умолчанию `Alert::make()` экранирует текст. Чтобы вставить `<a>`, `<strong>` и другие теги, вызовите `->html()`:

```php
Alert::make(
    'Все события — <a href="/bitrix/admin/vendor.php?page=events">перейти к списку</a>',
    Alert::INFO
)->html()
```

> Используйте `->html()` только со строками, которые вы формируете сами в коде, не с пользовательскими данными.

---

## Кастомный виджет

```php
use MB\Bitrix\AdminKit\Widget\AbstractWidget;

final class RecentOrdersWidget extends AbstractWidget
{
    public static function make(string $label): static
    {
        $w        = new static([]);
        $w->label = $label;

        return $w;
    }

    protected function renderWidget(): string
    {
        $label  = htmlspecialcharsbx($this->label);
        $orders = OrderTable::getList(['order' => ['DATE_CREATE' => 'DESC'], 'limit' => 5])->fetchAll();

        $rows = '';
        foreach ($orders as $o) {
            $rows .= '<li>#' . (int)$o['ID'] . ' — ' . htmlspecialcharsbx($o['STATUS']) . '</li>';
        }

        return <<<HTML
<div class="adminkit-widget__header">
    <span class="adminkit-widget__title">{$label}</span>
</div>
<ul style="margin:0;padding-left:18px;">{$rows}</ul>
HTML;
    }

    /** Дополнительные Bitrix-расширения для этого виджета (обычно пусто). */
    public function getRequiredExtensions(): array
    {
        return [];
    }
}
```

Использование:

```php
RecentOrdersWidget::make('Последние заказы')->span(6),
```

Полная справка — в [docs/dashboard-page.md](../dashboard-page.md).
