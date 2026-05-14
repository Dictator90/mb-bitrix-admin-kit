<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Widget;

/**
 * Chart widget powered by Chart.js (loaded from CDN, no Bitrix extension required).
 *
 * Usage — bar chart (default):
 *   GraphWidget::make('Orders by month')
 *       ->span(12)
 *       ->data([
 *           ['category' => 'Jan', 'value' => 42],
 *           ['category' => 'Feb', 'value' => 67],
 *       ])
 *       ->height(280)
 *
 * Usage — horizontal bar:
 *   GraphWidget::make('Top modules', 'bar')
 *       ->horizontal()
 *       ->categoryField('module')
 *       ->valueField('count')
 *       ->dataCallback(fn() => MyRepo::topModules())
 *       ->span(12)
 *
 * Usage — pie chart:
 *   GraphWidget::make('By status', 'pie')
 *       ->categoryField('title')
 *       ->data([['title' => 'Active', 'value' => 30], ['title' => 'Done', 'value' => 70]])
 *
 * Fine-tune with ->config() which deep-merges into the Chart.js options object.
 */
final class GraphWidget extends AbstractWidget
{
    /** Supported values: 'bar', 'line', 'pie', 'doughnut'. 'serial' is an alias for 'bar'. */
    private string $chartType = 'bar';

    /** @var array<int|string, mixed> */
    private array $data = [];

    /** @var (\Closure(): array<int|string, mixed>)|null */
    private ?\Closure $dataCallback = null;

    private int $height = 300;

    /** Field name in data rows used as chart labels (X axis / slice labels). */
    private string $categoryField = 'category';

    /** Field name in data rows used as numeric values. */
    private string $valueField = 'value';

    /** Horizontal bar chart (sets indexAxis: 'y'). */
    private bool $horizontal = false;

    /** Bar / line / pie color (CSS color string). Default: Bitrix blue. */
    private string $color = '#2fc6f6';

    /** @var array<string, mixed> Chart.js options to deep-merge over the generated config. */
    private array $chartConfig = [];

    /** Track whether the Chart.js CDN script was already emitted this request. */
    private static bool $scriptEmitted = false;

    public static function make(string $label = '', string $chartType = 'bar'): static
    {
        $widget            = new static([]);
        $widget->label     = $label;
        $widget->chartType = ($chartType === 'serial') ? 'bar' : $chartType;

        return $widget;
    }

    /** @param array<int|string, mixed> $data Rows with at least $categoryField and $valueField keys. */
    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Closure called at render time; must return an array of data rows.
     *
     * @param \Closure(): array<int|string, mixed> $fn
     */
    public function dataCallback(\Closure $fn): static
    {
        $this->dataCallback = $fn;

        return $this;
    }

    /** Chart container height in pixels (minimum 100). */
    public function height(int $px): static
    {
        $this->height = max(100, $px);

        return $this;
    }

    /** Which field in each row holds the category / label (default: 'category'). */
    public function categoryField(string $field): static
    {
        $this->categoryField = $field;

        return $this;
    }

    /** Which field in each row holds the numeric value (default: 'value'). */
    public function valueField(string $field): static
    {
        $this->valueField = $field;

        return $this;
    }

    /** Render the bar chart horizontally (indexAxis: 'y'). */
    public function horizontal(bool $horizontal = true): static
    {
        $this->horizontal = $horizontal;

        return $this;
    }

    /** Accent color for bars / slices / lines (CSS color string). Default: Bitrix blue. */
    public function barColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Deep-merge additional options into the generated Chart.js config.
     * Use this to override scales, plugins, colors, etc.
     *
     * @param array<string, mixed> $config
     */
    public function config(array $config): static
    {
        /** @var array<string, mixed> $merged */
        $merged            = array_replace_recursive($this->chartConfig, $config);
        $this->chartConfig = $merged;

        return $this;
    }

    /** No Bitrix extension required — Chart.js is embedded inline. */
    public function getRequiredExtensions(): array
    {
        return [];
    }

    /** @return array<int|string, mixed> */
    private function resolveData(): array
    {
        if ($this->dataCallback !== null) {
            return ($this->dataCallback)();
        }

        return $this->data;
    }

    /** Build a safe HTML/JS id (no dots). */
    private function buildChartId(): string
    {
        return 'adminkit-graph-' . str_replace('.', '-', uniqid('', true));
    }

    /**
     * @param array<int|string, mixed> $rows
     * @return array{labels: list<string>, values: list<mixed>}
     */
    private function parseRows(array $rows): array
    {
        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $labels[] = (string)($row[$this->categoryField] ?? '');
            $values[] = $row[$this->valueField] ?? 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /** @return array<string, mixed> */
    private function buildChartConfig(string $chartId): array
    {
        $rows   = $this->resolveData();
        $parsed = $this->parseRows($rows);

        $isPie = in_array($this->chartType, ['pie', 'doughnut'], true);

        $baseDataset = $isPie
            ? [
                'data'            => $parsed['values'],
                'backgroundColor' => [
                    '#2fc6f6', '#5ea831', '#ee8516', '#e22402',
                    '#7c6fcd', '#ff7043', '#26c6da', '#9ccc65',
                ],
                'borderWidth'     => 1,
            ]
            : [
                'data'            => $parsed['values'],
                'backgroundColor' => $this->color . 'cc',
                'borderColor'     => $this->color,
                'borderWidth'     => 1,
                'borderRadius'    => 4,
            ];

        $dataset = array_replace_recursive($baseDataset, $this->chartConfig['datasets'][0] ?? []);

        $options = [
            'responsive'          => true,
            'maintainAspectRatio' => false,
            'plugins'             => [
                'legend' => ['display' => $isPie],
            ],
        ];

        if (!$isPie) {
            $options['scales'] = [
                'x' => ['grid' => ['display' => false]],
                'y' => ['grid' => ['color' => 'rgba(0,0,0,0.05)'], 'ticks' => ['precision' => 0]],
            ];
        }

        if ($this->horizontal && $this->chartType === 'bar') {
            $options['indexAxis'] = 'y';
            // Swap scale defs for horizontal
            $options['scales'] = [
                'x' => ['grid' => ['color' => 'rgba(0,0,0,0.05)'], 'ticks' => ['precision' => 0]],
                'y' => ['grid' => ['display' => false]],
            ];
        }

        // Let user-supplied config override scales/plugins but keep datasets separate
        $userOptions = $this->chartConfig;
        unset($userOptions['datasets']);
        /** @var array<string, mixed> $options */
        $options = array_replace_recursive($options, $userOptions);

        return [
            'type'    => $this->chartType,
            'data'    => [
                'labels'   => $parsed['labels'],
                'datasets' => [$dataset],
            ],
            'options' => $options,
        ];
    }

    protected function renderWidget(): string
    {
        $chartId   = $this->buildChartId();
        $height    = $this->height;
        $label     = htmlspecialcharsbx($this->label);
        $chartConf = $this->buildChartConfig($chartId);

        $configJson = json_encode($chartConf, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $scriptTag = '';
        if (!self::$scriptEmitted) {
            self::$scriptEmitted = true;
            $scriptTag           = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>' . "\n";
        }

        return <<<HTML
{$scriptTag}<div class="adminkit-widget__header"><span class="adminkit-widget__title">{$label}</span></div>
<div style="height:{$height}px;position:relative;"><canvas id="{$chartId}"></canvas></div>
<script>
(function(){
    var el = document.getElementById('{$chartId}');
    if (!el) return;
    function init() {
        if (typeof Chart === 'undefined') {
            setTimeout(init, 50);
            return;
        }
        new Chart(el, {$configJson});
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
HTML;
    }
}
