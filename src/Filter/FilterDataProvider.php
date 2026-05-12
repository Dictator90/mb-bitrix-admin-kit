<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Filter;

use MB\Bitrix\AdminKit\Contracts\FilterContract;

/**
 * Utility for building the FILTER array expected by `bitrix:main.ui.filter`.
 * Not required by the Grid — the Grid builds filter params directly from FilterContract[].
 * Useful when you need the filter fields array separately.
 */
class FilterDataProvider
{
    /** @param FilterContract[] $filters */
    public function __construct(protected array $filters) {}

    /** @return array[] Field configs for bitrix:main.ui.filter FILTER param */
    public function getFields(): array
    {
        return array_map(fn(FilterContract $f) => $f->getFilterFieldConfig(), $this->filters);
    }

    /** @return FilterContract[] */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
