<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use MB\Bitrix\AdminKit\Contracts\ActionContract;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\FilterContract;
use MB\Bitrix\AdminKit\Contracts\IndexPageDefinitionContract;
use MB\Bitrix\AdminKit\Contracts\IndexResourceContract;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;

final class ResourceBackedIndexPageDefinition implements IndexPageDefinitionContract
{
    public function __construct(private readonly IndexResourceContract $resource)
    {
    }

    /** @return iterable<FieldContract> */
    public function fields(): iterable
    {
        return $this->resource->indexFields();
    }

    public function grouping(): ?IndexGrouping
    {
        return $this->resource->indexGrouping();
    }

    /** @return iterable<FilterContract> */
    public function filters(): iterable
    {
        return $this->resource->filters();
    }

    /** @return iterable<ActionContract> */
    public function rowActions(): iterable
    {
        return $this->resource->rowActions();
    }

    /** @return iterable<ActionContract> */
    public function bulkActions(): iterable
    {
        return $this->resource->bulkActions();
    }

    /** @return array<string,string> */
    public function defaultSort(): array
    {
        return $this->resource->defaultSort();
    }

    /** @return array<string,mixed> */
    public function defaultFilter(): array
    {
        return $this->resource->defaultFilter();
    }

    /** @return array<int,string> */
    public function defaultSelect(): array
    {
        return $this->resource->defaultSelect();
    }

    /** @return array<int,mixed> */
    public function runtimeFields(): array
    {
        return $this->resource->runtimeFields();
    }

    /** @return array<int,string> */
    public function indexSelect(GridContext $context): array
    {
        return $this->resource->indexSelect($context);
    }

    /** @return array<string,mixed> */
    public function indexFilter(GridContext $context): array
    {
        return $this->resource->indexFilter($context);
    }

    /** @return array<string,string> */
    public function indexOrder(GridContext $context): array
    {
        return $this->resource->indexOrder($context);
    }

    /** @return array<int,mixed> */
    public function indexRuntime(GridContext $context): array
    {
        return $this->resource->indexRuntime($context);
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function beforeIndexQueryParams(array $params, GridContext $context): array
    {
        return $this->resource->beforeIndexQueryParams($params, $context);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public function afterIndexRows(array $rows, GridContext $context): array
    {
        return $this->resource->afterIndexRows($rows, $context);
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    public function mapIndexRow(array $row, GridContext $context): array
    {
        return $this->resource->mapIndexRow($row, $context);
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function modifyIndexParams(array $params, GridContext $context): array
    {
        return $this->resource->modifyIndexParams($params, $context);
    }
}
