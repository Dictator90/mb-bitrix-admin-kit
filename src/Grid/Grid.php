<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid;

use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\ORM\Query\Result;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\PageNavigation;
use CUtil;
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Contracts\ActionContract;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\FilterContract;
use MB\Bitrix\AdminKit\Grid\Row\RowAssembler;
use MB\Bitrix\AdminKit\Support\AdminCollection;

class Grid
{
    protected GridOptions $gridOptions;
    protected PageNavigation $nav;
    protected string $filterId;

    protected array $rows = [];
    protected int $totalCount = 0;

    /** @var BulkAction[] */
    protected array $bulkActions = [];

    /**
     * @param FieldContract[] $fields
     * @param FilterContract[] $filters
     * @param ActionContract[] $rowActions
     */
    public function __construct(
        protected string $id,
        protected array $fields = [],
        protected array $filters = [],
        protected array $rowActions = [],
        protected string $baseUrl = '',
        protected string $primaryKey = 'ID',
        int $defaultPageSize = 20,
    ) {
        $this->filterId = $id . '_filter';
        $this->gridOptions = new GridOptions($id);

        $navParams = $this->gridOptions->getNavParams(['nPageSize' => $defaultPageSize]);

        $this->nav = new PageNavigation($id . '_nav');
        $this->nav
            ->allowAllRecords(true)
            ->setPageSize((int)$navParams['nPageSize'])
            ->initFromUri();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFilterId(): string
    {
        return $this->filterId;
    }

    public function getPagination(): PageNavigation
    {
        return $this->nav;
    }

    public function setTotalCount(int $count): void
    {
        $this->totalCount = $count;
        $this->nav->setRecordCount($count);
    }

    /** @param BulkAction[] $actions */
    public function setBulkActions(array $actions): void
    {
        $this->bulkActions = $actions;
    }

    /**
     * Feed an ORM result into the grid rows.
     * @param Result $result
     */
    public function setRawRows($result, ?GridContext $context = null): void
    {
        $assembler = new RowAssembler(
            $this->fields,
            $this->rowActions,
            $this->baseUrl,
            $this->primaryKey,
            $context?->resource,
            $context,
        );

        $this->rows = $assembler->buildRows($result);
    }

    /**
     * Returns ORM params derived from grid state (sort, filter, pagination).
     * @return array{select: string[], filter: array, order: array, limit: int, offset: int}
     */
    public function getOrmParams(): array
    {
        $sortParams = $this->gridOptions->getSorting([
            'sort' => [$this->primaryKey => 'DESC'],
            'vars' => ['by' => 'by', 'order' => 'order'],
        ]);

        $select = array_map(fn(FieldContract $f) => $f->getColumn(), AdminCollection::make($this->fields)->all());

        return [
            'select' => $select,
            'filter' => $this->buildOrmFilter(),
            'order' => $sortParams['sort'],
            'limit' => $this->nav->getLimit(),
            'offset' => $this->nav->getOffset(),
        ];
    }

    protected function buildOrmFilter(): array
    {
        if (empty($this->filters)) {
            return [];
        }

        $filterOptions = new FilterOptions($this->filterId);
        $rawValues = $filterOptions->getFilter();

        $result = [];
        foreach (AdminCollection::make($this->filters)->all() as $filter) {
            $value = $rawValues[$filter->getColumn()] ?? null;
            if ($value !== null && $value !== '' && !(is_array($value) && empty($value))) {
                $applied = $filter->apply($result, $value);
                $result = is_array($applied) ? $applied : $result;
            }
        }

        return $result;
    }

    /** Returns params array for `bitrix:main.ui.grid` component. */
    public function getGridComponentParams(): array
    {
        $sortParams = $this->gridOptions->getSorting([
            'sort' => [$this->primaryKey => 'DESC'],
            'vars' => ['by' => 'by', 'order' => 'order'],
        ]);

        $columns = array_map(fn(FieldContract $f) => $f->getGridColumnConfig(), AdminCollection::make($this->fields)->all());

        $params = [
            'GRID_ID' => $this->id,
            'COLUMNS' => $columns,
            'ROWS' => $this->rows,
            'SORT' => $sortParams['sort'],
            'SORT_VARS' => $sortParams['vars'],
            'NAV_OBJECT' => $this->nav,
            'TOTAL_ROWS_COUNT' => $this->totalCount,
            'SHOW_ROW_CHECKBOXES' => false,
            'SHOW_CHECK_ALL_CHECKBOXES' => false,
            'SHOW_ACTION_PANEL' => false,
            'ALLOW_COLUMNS_SORT' => true,
            'ALLOW_COLUMNS_RESIZE' => true,
            'ALLOW_HORIZONTAL_SCROLL' => true,
            'ALLOW_ROWS_SORT' => false,
            'AJAX_MODE' => 'Y',
            'AJAX_OPTION_HISTORY' => 'N',
            'AJAX_OPTION_JUMP' => 'N',
        ];

        if (!empty($this->bulkActions)) {
            $params['SHOW_ROW_CHECKBOXES'] = true;
            $params['SHOW_CHECK_ALL_CHECKBOXES'] = true;
            $params['SHOW_ACTION_PANEL'] = true;
            $params['ACTION_PANEL'] = $this->buildActionPanel();
        }

        return $params;
    }

    /** Returns params array for `bitrix:main.ui.filter` component, or null if no filters. */
    public function getFilterComponentParams(): ?array
    {
        if (empty($this->filters)) {
            return null;
        }

        $fields = array_map(fn(FilterContract $f) => $f->getFilterFieldConfig(), AdminCollection::make($this->filters)->all());

        return [
            'FILTER_ID' => $this->filterId,
            'GRID_ID' => $this->id,
            'FILTER' => $fields,
            'ENABLE_LIVE_SEARCH' => true,
            'ENABLE_LABEL' => true,
            'RESET_TO_DEFAULT_MODE' => true,
        ];
    }

    protected function buildActionPanel(): array
    {
        $items = [];
        foreach ($this->bulkActions as $action) {
            $item = [
                'TYPE' => 'BUTTON',
                'ID' => $action->getId(),
                'TEXT' => $action->getLabel(),
            ];

            if ($action->isDelete()) {
                $confirm = CUtil::JSEscape($action->getConfirmText() ?? 'Вы уверены?');
                $gridIdJs = CUtil::JSEscape($this->id);
                $item['ONCLICK'] =
                    "if(!confirm('{$confirm}'))return;" .
                    "var g=BX.Main.gridManager&&BX.Main.gridManager.getById('{$gridIdJs}');" .
                    "if(g&&g.grid){g.grid.getForm().submit('delete_bulk');}";
            }

            $items[] = $item;
        }

        return ['GROUPS' => [['ITEMS' => $items]]];
    }
}
