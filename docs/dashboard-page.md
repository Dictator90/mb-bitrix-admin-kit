# DashboardPage

`MB\Bitrix\AdminKit\Pages\DashboardPage` — standalone-страница для admin-обзоров и отчётов. Рендерит **12-колоночную CSS-сетку** и принимает встроенные виджеты, layout-компоненты и сырые HTML-строки.

---

## Минимальный пример

```php
<?php

namespace Vendor\Module\Admin;

use MB\Bitrix\AdminKit\Pages\DashboardPage;
use MB\Bitrix\AdminKit\Widget\CountWidget;

final class ModuleDashboard extends DashboardPage
{
    public static function getId(): string    { return 'dashboard'; }
    public static function getTitle(): string { return 'Dashboard'; }

    protected function widgets(): iterable
    {
        return [
            CountWidget::make('Products', ProductTable::class),
            CountWidget::make('Orders', OrderTable::class),
        ];
    }
}
```

Регистрация в admin-файле:

```php
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require_once __DIR__ . '/../include.php';

use Vendor\Module\Admin\ModuleDashboard;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
(new ModuleDashboard())->render();
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
```

---

## Сетка

Все элементы из `widgets()` рендерятся в 12-колоночной CSS-сетке (`display:grid; grid-template-columns: repeat(12, 1fr); gap: 16px`).

| Тип элемента | Span колонок |
|-----------|-------------|
| `CountWidget` (по умолчанию) | `span 3` — четыре в ряд |
| `GraphWidget` (по умолчанию) | `span 3` — для полной ширины используйте `->span(12)` |
| `AbstractLayoutComponent` (`Grid`, `Box`, `Flex`, …) | span не задаётся — при необходимости добавьте `->style('grid-column', 'span 12')` |
| Сырая `string` | `span 3` (обёртка для обратной совместимости) |

---

## Виджеты

### CountWidget

Показывает число записей из таблицы Bitrix ORM DataManager.

```php
use MB\Bitrix\AdminKit\Widget\CountWidget;

CountWidget::make('Products', ProductTable::class)
CountWidget::make('New orders', OrderTable::class)
    ->filter(['STATUS' => 'new'])               // ORM filter
    ->color('warning')                          // accent colour
    ->icon('--cart')                            // ui-icon-set CSS class
    ->href('/bitrix/admin/vendor_orders.php')   // make card a link
    ->span(3)                                   // grid columns (1-12, default 3)
```

**Своё значение** (обходит ORM count):
```php
CountWidget::make('Revenue', OrderTable::class)
    ->value(fn() => '$ ' . number_format(OrderRepo::totalRevenue()))
```

**Значения color:** `'primary'` (синий), `'success'` (зелёный), `'danger'` (красный), `'warning'` (оранжевый).

| Метод | Описание |
|--------|-------------|
| `make(string $label, string $tableClass)` | Фабрика; `$tableClass` должен быть подклассом DataManager |
| `filter(array $filter)` | ORM-фильтр для `getCount()` |
| `color(string $color)` | Акцентный цвет числа |
| `icon(string $cssClass)` | CSS-класс `ui-icon-set` (например, `'--cart'`, `'--user'`) |
| `href(string $url)` | Оборачивает карточку в ссылку |
| `value(\Closure $fn)` | Своё значение; обходит ORM — closure вызывается при рендере |
| `span(int $columns)` | Span колонок сетки 1–12 (по умолчанию: 3) |

---

### ChartWidget / GraphWidget

`ChartWidget` — основной виджет графика. `GraphWidget` сохранён как alias совместимости, расширяющий `ChartWidget`.
Инициализация графика идёт через локальное расширение `mb.admin.kit` с payload `data-adminkit-chart`; CDN script tag из PHP не вставляется.

```php
use MB\Bitrix\AdminKit\Widget\ChartWidget;

// Вертикальная столбчатая диаграмма (по умолчанию)
ChartWidget::make('Orders by month')
    ->span(12)
    ->data([
        ['category' => 'Jan', 'value' => 42],
        ['category' => 'Feb', 'value' => 67],
        ['category' => 'Mar', 'value' => 55],
    ])
    ->height(280)

// Горизонтальная столбчатая
ChartWidget::make('Top modules', 'bar')
    ->horizontal()
    ->categoryField('module')
    ->valueField('count')
    ->dataCallback(fn() => MyRepo::topModules(10))
    ->span(12)

// Круговая
ChartWidget::make('Status breakdown', 'pie')
    ->span(6)
    ->categoryField('title')
    ->data([
        ['title' => 'New',    'value' => 12],
        ['title' => 'Done',   'value' => 38],
        ['title' => 'Failed', 'value' => 5],
    ])

// Данные при рендере
ChartWidget::make('Users per day')
    ->span(12)
    ->dataCallback(fn() => UserRepo::registrationsPerDay(30))
    ->height(250)

// Свои опции Chart.js (deep-merge)
ChartWidget::make('Revenue (line)', 'line')
    ->span(12)
    ->data([...])
    ->config(['options' => ['tension' => 0.4]])
```

| Метод | Описание |
|--------|-------------|
| `make(string $label, string $chartType)` | `$chartType`: `'bar'` (по умолчанию), `'line'`, `'pie'`, `'doughnut'`. `'serial'` принимается как alias для `'bar'` |
| `data(array $data)` | Статический массив строк |
| `dataCallback(\Closure $fn)` | Closure со строками данных — вызывается при рендере |
| `categoryField(string $field)` | Ключ строки для подписи/категории (по умолчанию: `'category'`) |
| `valueField(string $field)` | Ключ строки для числового значения (по умолчанию: `'value'`) |
| `horizontal(bool $h = true)` | Горизонтальные столбцы (`indexAxis: 'y'`) |
| `barColor(string $css)` | Акцент для столбцов/линий (по умолчанию: синий Bitrix `#2fc6f6`) |
| `height(int $px)` | Высота контейнера графика в пикселях (по умолчанию: 300, минимум: 100) |
| `config(array $config)` | Deep-merge в сгенерированный объект конфигурации Chart.js |
| `span(int $columns)` | Span колонок сетки 1–12 (по умолчанию: 3) |

**Столбчатая по умолчанию** ожидает строки с ключами `category` и `value` (переопределите `->categoryField()` / `->valueField()`).
**Круговая по умолчанию** ожидает те же ключи — задайте `->categoryField('title')`, если в строках подпись в ключе `title`.

---

## Полная композиция layout

`widgets()` принимает любой `ComponentContract` — доступен весь набор (`Grid`, `Column`, `Box`, `Flex`, `Tabs`, `Alert`, …):

```php
use MB\Bitrix\AdminKit\Component\Layout\Box;
use MB\Bitrix\AdminKit\Component\Layout\Column;
use MB\Bitrix\AdminKit\Component\Layout\Grid;
use MB\Bitrix\AdminKit\Component\Alert;
use MB\Bitrix\AdminKit\Widget\CountWidget;
use MB\Bitrix\AdminKit\Widget\GraphWidget;

protected function widgets(): iterable
{
    return [
        // Четыре stat-карточки (span 3 = полный ряд)
        CountWidget::make('Products', ProductTable::class)->color('primary'),
        CountWidget::make('Orders',   OrderTable::class)->color('warning'),
        CountWidget::make('Users',    UserTable::class)->color('success'),
        CountWidget::make('Revenue',  OrderTable::class)
            ->value(fn() => '$ ' . number_format(OrderRepo::revenue())),

        // График на всю ширину
        GraphWidget::make('Orders by month')
            ->span(12)
            ->dataCallback(fn() => OrderRepo::byMonth()),

        // Layout на всю ширину с вложенными виджетами
        Grid::make([
            Column::make([
                CountWidget::make('Pending', OrderTable::class)->filter(['STATUS' => 'pending']),
            ])->span(4),
            Column::make([
                Alert::make('Sync OK — last run 5 min ago', Alert::SUCCESS),
            ])->span(8),
        ])->style('grid-column', 'span 12'),
    ];
}
```

---

## Общий API виджетов (`AbstractWidget`)

Все виджеты наследуют `AbstractWidget → AbstractLayoutComponent`, что даёт:

```php
$widget
    ->class('my-extra-class')               // CSS-классы внешнего div
    ->style('border-color', '#f00')         // inline CSS (не переопределяет grid-column)
    ->attr('data-id', '42')                 // произвольные HTML-атрибуты
    ->label('Custom label')                 // обновить подпись после создания
    ->icon('--info')                        // CSS-класс иконки
    ->span(6)                               // grid-column: span 6
```

---

## Свои виджеты

Расширьте `AbstractWidget` и реализуйте `renderWidget(): string`:

```php
use MB\Bitrix\AdminKit\Widget\AbstractWidget;

final class RecentActivityWidget extends AbstractWidget
{
    public static function make(string $label): static
    {
        $w        = new static([]);
        $w->label = $label;

        return $w;
    }

    protected function renderWidget(): string
    {
        $label = htmlspecialcharsbx($this->label);
        $rows  = ActivityLog::recent(5);

        $items = '';
        foreach ($rows as $row) {
            $items .= '<li>' . htmlspecialcharsbx($row['text']) . '</li>';
        }

        return <<<HTML
<div class="adminkit-widget__header"><span class="adminkit-widget__title">{$label}</span></span>
<ul>{$items}</ul>
HTML;
    }

    /** Расширения Bitrix для загрузки перед рендером (через Extension::load). Обычно пусто для своих виджетов. */
    public function getRequiredExtensions(): array
    {
        return [];
    }
}
```

Использование в `widgets()`:
```php
RecentActivityWidget::make('Recent activity')->span(12),
```
