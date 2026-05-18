# DashboardPage

`MB\Bitrix\AdminKit\Pages\DashboardPage` is a standalone page for building admin overview/reporting screens. It renders a **12-column CSS grid** and accepts any mix of built-in widgets, layout components, and raw HTML strings.

---

## Minimal example

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

Register in the admin file:

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

## Grid layout

All items returned from `widgets()` are rendered inside a 12-column CSS grid (`display:grid; grid-template-columns: repeat(12, 1fr); gap: 16px`).

| Item type | Column span |
|-----------|-------------|
| `CountWidget` (default) | `span 3` — four per row |
| `GraphWidget` (default) | `span 3` — use `->span(12)` for full width |
| `AbstractLayoutComponent` (`Grid`, `Box`, `Flex`, …) | no span applied — add `->style('grid-column', 'span 12')` as needed |
| Raw `string` | `span 3` (backward compat wrapper) |

---

## Widgets

### CountWidget

Displays a record count from a Bitrix ORM DataManager table.

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

**Custom value** (bypasses ORM count):
```php
CountWidget::make('Revenue', OrderTable::class)
    ->value(fn() => '$ ' . number_format(OrderRepo::totalRevenue()))
```

**Color values:** `'primary'` (blue), `'success'` (green), `'danger'` (red), `'warning'` (orange).

| Method | Description |
|--------|-------------|
| `make(string $label, string $tableClass)` | Factory; `$tableClass` must be a DataManager subclass |
| `filter(array $filter)` | ORM filter passed to `getCount()` |
| `color(string $color)` | Accent colour on the value number |
| `icon(string $cssClass)` | `ui-icon-set` icon class (e.g. `'--cart'`, `'--user'`) |
| `href(string $url)` | Wrap the card in a link |
| `value(\Closure $fn)` | Custom value; bypasses ORM — the closure is called at render time |
| `span(int $columns)` | Grid column span 1–12 (default: 3) |

---

### ChartWidget / GraphWidget

`ChartWidget` is the primary chart widget. `GraphWidget` is kept as a compatibility alias that extends `ChartWidget`.
Chart rendering is initialized through the local `mb.admin.kit` extension with `data-adminkit-chart` payload; no CDN script tag is injected from PHP.

```php
use MB\Bitrix\AdminKit\Widget\ChartWidget;

// Vertical bar chart (default)
ChartWidget::make('Orders by month')
    ->span(12)
    ->data([
        ['category' => 'Jan', 'value' => 42],
        ['category' => 'Feb', 'value' => 67],
        ['category' => 'Mar', 'value' => 55],
    ])
    ->height(280)

// Horizontal bar chart
ChartWidget::make('Top modules', 'bar')
    ->horizontal()
    ->categoryField('module')
    ->valueField('count')
    ->dataCallback(fn() => MyRepo::topModules(10))
    ->span(12)

// Pie chart
ChartWidget::make('Status breakdown', 'pie')
    ->span(6)
    ->categoryField('title')
    ->data([
        ['title' => 'New',    'value' => 12],
        ['title' => 'Done',   'value' => 38],
        ['title' => 'Failed', 'value' => 5],
    ])

// Data loaded at render time
ChartWidget::make('Users per day')
    ->span(12)
    ->dataCallback(fn() => UserRepo::registrationsPerDay(30))
    ->height(250)

// Custom Chart.js options (deep-merged)
ChartWidget::make('Revenue (line)', 'line')
    ->span(12)
    ->data([...])
    ->config(['options' => ['tension' => 0.4]])
```

| Method | Description |
|--------|-------------|
| `make(string $label, string $chartType)` | `$chartType`: `'bar'` (default), `'line'`, `'pie'`, `'doughnut'`. `'serial'` is accepted as alias for `'bar'` |
| `data(array $data)` | Static array of rows |
| `dataCallback(\Closure $fn)` | Closure returning data rows — called at render time |
| `categoryField(string $field)` | Row key used as label/category (default: `'category'`) |
| `valueField(string $field)` | Row key used as numeric value (default: `'value'`) |
| `horizontal(bool $h = true)` | Render bar chart horizontally (`indexAxis: 'y'`) |
| `barColor(string $css)` | Accent color for bars/lines (default: Bitrix blue `#2fc6f6`) |
| `height(int $px)` | Chart container height in pixels (default: 300, minimum: 100) |
| `config(array $config)` | Deep-merged into the generated Chart.js config object |
| `span(int $columns)` | Grid column span 1–12 (default: 3) |

**Default bar chart** expects rows with keys `category` and `value` (override with `->categoryField()` / `->valueField()`).
**Default pie chart** expects rows with the same keys — set `->categoryField('title')` if your rows use a `title` key for labels.

---

## Full layout composition

`widgets()` accepts any `ComponentContract` — the full toolkit (`Grid`, `Column`, `Box`, `Flex`, `Tabs`, `Alert`, …) can be used:

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
        // Four stat cards (span 3 each = one full row)
        CountWidget::make('Products', ProductTable::class)->color('primary'),
        CountWidget::make('Orders',   OrderTable::class)->color('warning'),
        CountWidget::make('Users',    UserTable::class)->color('success'),
        CountWidget::make('Revenue',  OrderTable::class)
            ->value(fn() => '$ ' . number_format(OrderRepo::revenue())),

        // Full-width chart
        GraphWidget::make('Orders by month')
            ->span(12)
            ->dataCallback(fn() => OrderRepo::byMonth()),

        // Full-width layout component with nested widgets
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

## Shared widget API (`AbstractWidget`)

All widgets inherit `AbstractWidget → AbstractLayoutComponent` which provides:

```php
$widget
    ->class('my-extra-class')               // add CSS classes to the outer div
    ->style('border-color', '#f00')         // add inline CSS (does not override grid-column)
    ->attr('data-id', '42')                 // add arbitrary HTML attributes
    ->label('Custom label')                 // update the label after construction
    ->icon('--info')                        // icon CSS class
    ->span(6)                               // grid-column: span 6
```

---

## Custom widgets

Extend `AbstractWidget` and implement `renderWidget(): string`:

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
<div class="adminkit-widget__header"><span class="adminkit-widget__title">{$label}</span></div>
<ul>{$items}</ul>
HTML;
    }

    /** Bitrix extensions to load before rendering (via Extension::load). Usually empty for custom widgets. */
    public function getRequiredExtensions(): array
    {
        return [];
    }
}
```

Use in `widgets()`:
```php
RecentActivityWidget::make('Recent activity')->span(12),
```
