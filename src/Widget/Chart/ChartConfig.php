<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Widget\Chart;

final class ChartConfig
{
    /** @param list<string> $labels */
    /** @param list<ChartDataset> $datasets */
    /** @param array<string,mixed> $options */
    public function __construct(
        public readonly string $type,
        public readonly array $labels,
        public readonly array $datasets,
        public readonly array $options = [],
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'data' => [
                'labels' => $this->labels,
                'datasets' => array_map(static fn (ChartDataset $dataset): array => $dataset->toArray(), $this->datasets),
            ],
            'options' => $this->options,
        ];
    }
}
