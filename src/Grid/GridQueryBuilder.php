<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid;

use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\FilterContract;
use MB\Bitrix\AdminKit\Contracts\IndexPageDefinitionContract;
use MB\Bitrix\AdminKit\Contracts\IndexResourceContract;
use MB\Bitrix\AdminKit\Contracts\OrmResourceContract;
use MB\Bitrix\AdminKit\Grid\Relations\RelationFieldContract;
use MB\Bitrix\AdminKit\Page\ResourceBackedIndexPageDefinition;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class GridQueryBuilder
{
    public function build(
        IndexResourceContract&OrmResourceContract $resource,
        GridContext $context,
        ?IndexPageDefinitionContract $indexPage = null,
    ): array {
        $indexPage ??= $this->resourceBackedDefinition($resource);

        $params = [
            'select' => $this->buildSelect($resource, $context, $indexPage),
            'filter' => $this->buildFilter($context, $indexPage),
            'order' => $this->buildOrder($context, $indexPage),
            'limit' => $context->limit,
            'offset' => $context->offset,
        ];

        $runtime = $this->buildRuntime($context, $indexPage);
        if ($runtime !== []) {
            $params['runtime'] = $runtime;
        }

        $params = $indexPage->beforeIndexQueryParams($params, $context);

        return $indexPage->modifyIndexParams($params, $context);
    }

    private function buildSelect(
        OrmResourceContract $resource,
        GridContext $context,
        IndexPageDefinitionContract $indexPage,
    ): array {
        $select = [];
        foreach (AdminCollection::make($indexPage->fields())->all() as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }
            if ($field instanceof RelationFieldContract) {
                $select[] = $field->relationLocalKey();
                continue;
            }
            if (method_exists($field, 'isComputed') && $field->isComputed()) {
                continue;
            }
            $select[] = $field->getColumn();
        }

        $select = array_merge($select, $indexPage->defaultSelect(), $indexPage->indexSelect($context));

        $primaryKey = $resource->getPrimaryKey();
        if (!in_array($primaryKey, $select, true)) {
            $select[] = $primaryKey;
        }

        return array_values(array_unique(array_filter($select)));
    }

    private function buildOrder(GridContext $context, IndexPageDefinitionContract $indexPage): array
    {
        $uiOrder = $context->sort;
        if ($uiOrder === []) {
            $uiOrder = $this->readGridSort($context);
        }
        if ($uiOrder === []) {
            $uiOrder = $this->readRequestSort($context);
        }

        if ($uiOrder !== []) {
            return $uiOrder;
        }

        return array_replace($indexPage->defaultSort(), $indexPage->indexOrder($context));
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

    /**
     * @return array<string, string>
     */
    private function readRequestSort(GridContext $context): array
    {
        $request = $context->request;
        if (!$request instanceof HttpRequest) {
            return [];
        }

        $by = (string)$request->get('by');
        if ($by === '') {
            return [];
        }

        $order = strtoupper((string)$request->get('order'));

        return [$by => $order === 'DESC' ? 'DESC' : 'ASC'];
    }

    private function buildFilter(GridContext $context, IndexPageDefinitionContract $indexPage): array
    {
        $rawValues = $context->filter;
        if ($rawValues === [] && class_exists(FilterOptions::class)) {
            $rawValues = (new FilterOptions($context->filterId))->getFilter();
        }

        $result = [];
        foreach (AdminCollection::make($indexPage->filters())->all() as $filter) {
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

        return array_replace($indexPage->defaultFilter(), $result, $indexPage->indexFilter($context));
    }

    private function buildRuntime(GridContext $context, IndexPageDefinitionContract $indexPage): array
    {
        return array_values(array_merge(
            AdminCollection::make($indexPage->runtimeFields())->all(),
            AdminCollection::make($indexPage->indexRuntime($context))->all(),
        ));
    }

    private function resourceBackedDefinition(IndexResourceContract $resource): IndexPageDefinitionContract
    {
        return new ResourceBackedIndexPageDefinition($resource);
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
