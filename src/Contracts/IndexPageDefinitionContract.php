<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

use MB\Bitrix\AdminKit\Grid\GridContext;

interface IndexPageDefinitionContract
{
    /** @return iterable<FieldContract> */
    public function fields(): iterable;

    /** @return iterable<FilterContract> */
    public function filters(): iterable;

    /** @return iterable<ActionContract> */
    public function rowActions(): iterable;

    /** @return iterable<ActionContract> */
    public function bulkActions(): iterable;

    /** @return array<string,string> */
    public function defaultSort(): array;

    /** @return array<string,mixed> */
    public function defaultFilter(): array;

    /** @return array<int,string> */
    public function defaultSelect(): array;

    /** @return array<int,mixed> */
    public function runtimeFields(): array;

    /** @return array<int,string> */
    public function indexSelect(GridContext $context): array;

    /** @return array<string,mixed> */
    public function indexFilter(GridContext $context): array;

    /** @return array<string,string> */
    public function indexOrder(GridContext $context): array;

    /** @return array<int,mixed> */
    public function indexRuntime(GridContext $context): array;

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function beforeIndexQueryParams(array $params, GridContext $context): array;

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public function afterIndexRows(array $rows, GridContext $context): array;

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    public function mapIndexRow(array $row, GridContext $context): array;

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    public function modifyIndexParams(array $params, GridContext $context): array;
}
