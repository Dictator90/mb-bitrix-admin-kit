<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid;

use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\FilterContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;

final class GridQueryBuilder
{
    public function build(ResourceContract $resource, GridContext $context): array
    {
        $params = [
            'select' => $this->buildSelect($resource),
            'filter' => array_replace($resource->defaultFilter(), $this->buildFilter($resource, $context)),
            'order' => $context->sort ?: $this->buildSort($resource, $context),
            'limit' => $context->limit,
            'offset' => $context->offset,
        ];

        $runtime = $resource->runtimeFields();
        if ($runtime !== []) {
            $params['runtime'] = $runtime;
        }

        return $resource->modifyIndexParams($params, $context);
    }

    private function buildSelect(ResourceContract $resource): array
    {
        $select = $resource->defaultSelect();
        foreach ($resource->indexFields() as $field) {
            if ($field instanceof FieldContract) {
                $select[] = $field->getColumn();
            }
        }

        $primaryKey = $resource->getPrimaryKey();
        if (!in_array($primaryKey, $select, true)) {
            $select[] = $primaryKey;
        }

        return array_values(array_unique(array_filter($select)));
    }

    private function buildSort(ResourceContract $resource, GridContext $context): array
    {
        if (class_exists(GridOptions::class)) {
            $sortParams = (new GridOptions($context->gridId))->getSorting([
                'sort' => $resource->defaultSort(),
                'vars' => ['by' => 'by', 'order' => 'order'],
            ]);
            return $sortParams['sort'] ?? $resource->defaultSort();
        }

        return $resource->defaultSort();
    }

    private function buildFilter(ResourceContract $resource, GridContext $context): array
    {
        $rawValues = $context->filter;
        if ($rawValues === [] && class_exists(FilterOptions::class)) {
            $rawValues = (new FilterOptions($context->filterId))->getFilter();
        }

        $result = [];
        foreach ($resource->filters() as $filter) {
            if (!$filter instanceof FilterContract) {
                continue;
            }

            $column = $filter->getColumn();
            $value = $rawValues[$column] ?? null;
            if ($this->isEmptyFilterValue($value)) {
                continue;
            }

            $result = $filter->apply($result, $value);
        }

        return $result;
    }

    private function isEmptyFilterValue(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }
}
