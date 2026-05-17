<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid;

use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\ORM\Query\Result;
use Bitrix\Main\UI\PageNavigation;
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;
use MB\Bitrix\AdminKit\Bitrix\Grid\BitrixFilterAdapter;
use MB\Bitrix\AdminKit\Bitrix\Grid\BitrixGridAdapter;
use MB\Bitrix\AdminKit\Contracts\Action\BulkPanelItemContract;
use MB\Bitrix\AdminKit\Contracts\ActionContract;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\FilterContract;
use MB\Bitrix\AdminKit\Contracts\IndexPageDefinitionContract;
use MB\Bitrix\AdminKit\Grid\Row\RowAssembler;

class Grid
{
    protected GridOptions $gridOptions;
    protected PageNavigation $nav;
    protected string $filterId;

    protected array $rows = [];
    protected int $totalCount = 0;
    protected bool $collapsibleRows = false;
    protected ?string $collapsibleShiftColumnId = null;
    protected ?string $groupingAlign = null;
    protected ?bool $showSelectAllRecordsCheckbox = null;

    /** @var BulkPanelItemContract[] */
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

    public function limitPageSize(int $maxPageSize): void
    {
        $maxPageSize = max(1, $maxPageSize);
        if ($this->nav->getPageSize() > $maxPageSize) {
            $this->nav->setPageSize($maxPageSize);
        }
    }

    public function setTotalCount(int $count): void
    {
        $this->totalCount = $count;
        $this->nav->setRecordCount($count);
    }

    /** @param BulkPanelItemContract[] $actions */
    public function setBulkActions(array $actions): void
    {
        $this->bulkActions = $actions;
    }

    /**
     * Feed an ORM result into the grid rows.
     * @param Result $result
     */
    public function setRawRows(
        $result,
        ?GridContext $context = null,
        ?IndexPageDefinitionContract $indexPage = null,
    ): void {
        $assembler = new RowAssembler(
            $this->fields,
            $this->rowActions,
            $this->baseUrl,
            $this->primaryKey,
            $context?->resource,
            $context,
            $indexPage,
        );

        $this->rows = $assembler->buildRows($result);
    }

    /** Returns params array for `bitrix:main.ui.grid` component. */
    public function getGridComponentParams(): array
    {
        return (new BitrixGridAdapter())->componentParams($this);
    }

    /** Returns params array for `bitrix:main.ui.filter` component, or null if no filters. */
    public function getFilterComponentParams(): ?array
    {
        return (new BitrixFilterAdapter())->componentParams($this);
    }

    public function enableCollapsibleRows(bool $enabled = true, ?string $shiftColumnId = null): void
    {
        $this->collapsibleRows = $enabled;
        $this->collapsibleShiftColumnId = $enabled ? $shiftColumnId : null;
    }

    public function hasCollapsibleRows(): bool
    {
        return $this->collapsibleRows;
    }

    public function collapsibleShiftColumnId(): ?string
    {
        return $this->collapsibleShiftColumnId;
    }

    public function setGroupingAlign(?string $align): void
    {
        $this->groupingAlign = $align;
    }

    public function groupingAlign(): ?string
    {
        return $this->groupingAlign;
    }

    /** @return FieldContract[] */
    public function getFields(): array
    {
        return $this->fields;
    }

    /** @return FilterContract[] */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /** @return array<int,array<string,mixed>> */
    public function getRows(): array
    {
        return $this->rows;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /** @return BulkPanelItemContract[] */
    public function getBulkActions(): array
    {
        return $this->bulkActions;
    }

    /** @return list<BulkAction> */
    public function getExecutableBulkActions(): array
    {
        $executable = [];
        foreach ($this->bulkActions as $item) {
            if ($item instanceof BulkAction) {
                $executable[] = $item;
            } elseif ($item instanceof BulkActionDropdown) {
                foreach ($item->getItems() as $child) {
                    $executable[] = $child;
                }
            }
        }

        return $executable;
    }

    public function hasEditableFields(): bool
    {
        foreach ($this->fields as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }

            $editable = $field->getGridColumnConfig()['editable'] ?? false;
            if ($editable !== false && $editable !== null) {
                return true;
            }
        }

        return false;
    }

    public function showSelectAllRecordsCheckbox(bool $show = true): static
    {
        $this->showSelectAllRecordsCheckbox = $show;

        return $this;
    }

    public function hasRunByFilterBulkActions(): bool
    {
        foreach ($this->getExecutableBulkActions() as $action) {
            if ($action->canRunByFilter()) {
                return true;
            }
        }

        return false;
    }

    public function shouldShowSelectAllRecordsCheckbox(): bool
    {
        return $this->showSelectAllRecordsCheckbox
            ?? $this->hasRunByFilterBulkActions();
    }
}
