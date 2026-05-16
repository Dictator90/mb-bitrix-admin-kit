<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Widget\Chart;

final class ChartDataset
{
    /** @param list<mixed> $data */
    /** @param array<string,mixed> $options */
    public function __construct(
        public readonly array $data,
        public readonly array $options = [],
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return array_merge(['data' => $this->data], $this->options);
    }
}
