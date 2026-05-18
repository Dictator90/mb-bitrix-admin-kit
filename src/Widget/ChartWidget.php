<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Widget;

use MB\Bitrix\AdminKit\Widget\Chart\ChartConfig;
use MB\Bitrix\AdminKit\Widget\Chart\ChartDataset;
use MB\Bitrix\AdminKit\Widget\Renderers\ChartWidgetRenderer;

class ChartWidget extends AbstractWidget
{
    protected string $chartType = 'bar';

    /** @var array<int|string,mixed> */
    protected array $data = [];

    /** @var (\Closure(): array<int|string,mixed>)|null */
    protected ?\Closure $dataCallback = null;

    protected int $height = 300;
    protected string $categoryField = 'category';
    protected string $valueField = 'value';
    protected bool $horizontal = false;
    protected string $color = '#2fc6f6';

    /** @var array<string,mixed> */
    protected array $chartConfig = [];

    public static function make(string $label = '', string $chartType = 'bar'): static
    {
        $widget = new static();
        $widget->label = $label;
        $widget->chartType = ($chartType === 'serial') ? 'bar' : $chartType;

        return $widget;
    }

    /** @param array<int|string,mixed> $data */
    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /** @param \Closure(): array<int|string,mixed> $fn */
    public function dataCallback(\Closure $fn): static
    {
        $this->dataCallback = $fn;

        return $this;
    }

    public function height(int $px): static
    {
        $this->height = max(100, $px);

        return $this;
    }

    public function categoryField(string $field): static
    {
        $this->categoryField = $field;

        return $this;
    }

    public function valueField(string $field): static
    {
        $this->valueField = $field;

        return $this;
    }

    public function horizontal(bool $horizontal = true): static
    {
        $this->horizontal = $horizontal;

        return $this;
    }

    public function barColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    /** @param array<string,mixed> $config */
    public function config(array $config): static
    {
        $this->chartConfig = array_replace_recursive($this->chartConfig, $config);

        return $this;
    }

    public function getRequiredExtensions(): array
    {
        return ['mb.admin.kit'];
    }

    protected function renderWidget(): string
    {
        $chartId = 'adminkit-chart-' . str_replace('.', '-', uniqid('', true));
        $config = $this->buildChartConfig()->toArray();

        return (new ChartWidgetRenderer())->render($this->label, $chartId, $this->height, $config);
    }

    /** @return array<int|string,mixed> */
    protected function resolveData(): array
    {
        if ($this->dataCallback !== null) {
            return ($this->dataCallback)();
        }

        return $this->data;
    }

    /** @return array{labels:list<string>,values:list<mixed>} */
    protected function parseRows(array $rows): array
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

    protected function buildChartConfig(): ChartConfig
    {
        $parsed = $this->parseRows($this->resolveData());
        $isPie = in_array($this->chartType, ['pie', 'doughnut'], true);
        $datasetOptions = $isPie
            ? [
                'backgroundColor' => ['#2fc6f6', '#5ea831', '#ee8516', '#e22402', '#7c6fcd', '#ff7043', '#26c6da', '#9ccc65'],
                'borderWidth' => 1,
            ]
            : [
                'backgroundColor' => $this->color . 'cc',
                'borderColor' => $this->color,
                'borderWidth' => 1,
                'borderRadius' => 4,
            ];
        $datasetOptions = array_replace_recursive($datasetOptions, $this->chartConfig['datasets'][0] ?? []);

        $options = [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => ['legend' => ['display' => $isPie]],
        ];
        if (!$isPie) {
            $options['scales'] = [
                'x' => ['grid' => ['display' => false]],
                'y' => ['grid' => ['color' => 'rgba(0,0,0,0.05)'], 'ticks' => ['precision' => 0]],
            ];
        }
        if ($this->horizontal && $this->chartType === 'bar') {
            $options['indexAxis'] = 'y';
        }

        $userOptions = $this->chartConfig;
        unset($userOptions['datasets']);
        $options = array_replace_recursive($options, $userOptions);

        return new ChartConfig(
            type: $this->chartType,
            labels: $parsed['labels'],
            datasets: [new ChartDataset($parsed['values'], $datasetOptions)],
            options: $options,
        );
    }
}
