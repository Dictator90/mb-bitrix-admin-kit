<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid;

use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\FilterContract;
use MB\Bitrix\AdminKit\Contracts\IndexPageDefinitionContract;
use MB\Bitrix\AdminKit\Contracts\Resource\CrudResourceContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceOrmContract;
use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;
use MB\Bitrix\AdminKit\Grid\Relations\RelationFieldContract;
use MB\Bitrix\AdminKit\Page\ResourceBackedIndexPageDefinition;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class GridQueryBuilder
{
    public function build(
        CrudResourceContract&ResourceOrmContract $resource,
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
        ResourceOrmContract $resource,
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
            if (method_exists($field, 'isSelectable') && !$field->isSelectable()) {
                continue;
            }
            if (method_exists($field, 'getSelectColumns')) {
                foreach ($field->getSelectColumns() as $col) {
                    $select[] = $col;
                }
                continue;
            }
            $select[] = $field->getColumn();
        }

        $select = array_merge($select, $indexPage->defaultSelect(), $indexPage->indexSelect($context));

        $primaryKey = $resource->getPrimaryKey();
        if (!in_array($primaryKey, $select, true)) {
            $select[] = $primaryKey;
        }

        $grouping = $indexPage->grouping();
        if ($grouping instanceof IndexGrouping) {
            $foreignKey = $grouping->foreignKey();
            if ($foreignKey !== '' && !in_array($foreignKey, $select, true)) {
                $select[] = $foreignKey;
            }
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

        $uiOrder = $this->sanitizeOrder($uiOrder, $indexPage);

        if ($uiOrder !== []) {
            return $uiOrder;
        }

        return array_replace($indexPage->defaultSort(), $indexPage->indexOrder($context));
    }

    private function allowedSortColumns(IndexPageDefinitionContract $indexPage): array
    {
        $allowed = [];
        foreach (AdminCollection::make($indexPage->fields())->all() as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }

            $config = $field->getGridColumnConfig();
            $sort = $config['sort'] ?? false;

            if ($sort === false) {
                continue;
            }

            if ($sort === true) {
                $allowed[] = $field->getColumn();
            } elseif (is_string($sort)) {
                $allowed[] = $sort;
            } elseif (is_array($sort)) {
                foreach ($sort as $k => $v) {
                    $allowed[] = is_string($k) ? $k : $v;
                }
            }
        }

        return array_unique(array_filter($allowed));
    }

    private function sanitizeOrder(array $order, IndexPageDefinitionContract $indexPage): array
    {
        if ($order === []) {
            return [];
        }

        $allowedColumns = $this->allowedSortColumns($indexPage);
        $sanitized = [];

        foreach ($order as $column => $direction) {
            if (!in_array($column, $allowedColumns, true)) {
                continue;
            }

            $direction = strtoupper((string)$direction);
            if (!in_array($direction, ['ASC', 'DESC'], true)) {
                $direction = 'ASC';
            }

            $sanitized[$column] = $direction;
        }

        return $sanitized;
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

    private function resourceBackedDefinition(CrudResourceContract $resource): IndexPageDefinitionContract
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
