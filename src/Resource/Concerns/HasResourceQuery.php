<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

use MB\Bitrix\AdminKit\Grid\GridContext;

trait HasResourceQuery
{
    public function defaultSort(): array
    {
        return [$this->getPrimaryKey() => 'DESC'];
    }

    public function defaultFilter(): array
    {
        return [];
    }

    public function defaultSelect(): array
    {
        return [];
    }

    public function runtimeFields(): array
    {
        return [];
    }

    public function indexSelect(GridContext $context): array
    {
        return [];
    }

    public function indexFilter(GridContext $context): array
    {
        return [];
    }

    public function indexOrder(GridContext $context): array
    {
        return [];
    }

    public function indexRuntime(GridContext $context): array
    {
        return [];
    }

    public function beforeIndexQueryParams(array $params, GridContext $context): array
    {
        return $params;
    }

    public function afterIndexRows(array $rows, GridContext $context): array
    {
        return $rows;
    }

    public function mapIndexRow(array $row, GridContext $context): array
    {
        return $row;
    }

    public function modifyIndexParams(array $params, GridContext $context): array
    {
        return $params;
    }
}
