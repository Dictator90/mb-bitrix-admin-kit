<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid;

use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\FilterContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class GridQueryBuilder
{
    public function build(ResourceContract $resource, GridContext $context): array
    {
        $params = [
            'select' => $this->buildSelect($resource, $context),
            'filter' => $this->buildFilter($resource, $context),
            'order' => $this->buildOrder($resource, $context),
            'limit' => $context->limit,
            'offset' => $context->offset,
        ];

        $runtime = $this->buildRuntime($resource, $context);
        if ($runtime !== []) {
            $params['runtime'] = $runtime;
        }

        $params = $resource->beforeIndexQueryParams($params, $context);

        return $resource->modifyIndexParams($params, $context);
    }

    private function buildSelect(ResourceContract $resource, GridContext $context): array
    {
        $select = [];
        foreach (AdminCollection::make($resource->indexFields())->all() as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }
            if (method_exists($field, 'isComputed') && $field->isComputed()) {
                continue;
            }
            $select[] = $field->getColumn();
        }

        $select = array_merge($select, $resource->defaultSelect(), $resource->indexSelect($context));

        $primaryKey = $resource->getPrimaryKey();
        if (!in_array($primaryKey, $select, true)) {
            $select[] = $primaryKey;
        }

        return array_values(array_unique(array_filter($select)));
    }

    private function buildOrder(ResourceContract $resource, GridContext $context): array
    {
        $uiOrder = $context->sort ?: $this->readGridSort($context);

        return array_replace($resource->defaultSort(), $uiOrder, $resource->indexOrder($context));
    }

    private function readGridSort(GridContext $context): array
    {
        if (class_exists(GridOptions::class)) {
            $sortParams = (new GridOptions($context->gridId))->getSorting([
                'sort' => [],
                'vars' => ['by' => 'by', 'order' => 'order'],
            ]);

            return $sortParams['sort'] ?? [];
        }

        return [];
    }

    private function buildFilter(ResourceContract $resource, GridContext $context): array
    {
        $rawValues = $context->filter;
        if ($rawValues === [] && class_exists(FilterOptions::class)) {
            $rawValues = (new FilterOptions($context->filterId))->getFilter();
        }

        $result = [];
        foreach (AdminCollection::make($resource->filters())->all() as $filter) {
            if (!$filter instanceof FilterContract) {
                continue;
            }

            $column = $filter->getColumn();
            $value = $rawValues[$column] ?? $rawValues[$this->safeKey($column)] ?? null;
            if ($this->isEmptyFilterValue($value)) {
                continue;
            }

            $result = $filter->applyToOrmFilter($result, $value, $context);
        }

        return array_replace($resource->defaultFilter(), $result, $resource->indexFilter($context));
    }

    private function buildRuntime(ResourceContract $resource, GridContext $context): array
    {
        return array_values(array_merge(
            AdminCollection::make($resource->runtimeFields())->all(),
            AdminCollection::make($resource->indexRuntime($context))->all(),
        ));
    }

    private function safeKey(string $column): string
    {
        return strtoupper(str_replace(['.', '-'], '_', $column));
    }

    private function isEmptyFilterValue(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }
}
