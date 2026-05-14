<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use Bitrix\Main\UI\Extension;
use Bitrix\UI\Buttons\Button;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Toolbar\ButtonLocation;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Bitrix\AdminKit\Support\UrlGenerator;

class IndexPage extends Page
{
    protected ?Grid $grid = null;

    public function __construct(ResourceContract $resource)
    {
        parent::__construct($resource);
    }

    public function render(): void
    {
        global $APPLICATION;

        // Handle single-row delete (from RowAction)
        $action = $this->request->get('action') ?: $this->request->getPost('action');

        if ($action === 'delete' && check_bitrix_sessid()) {
            $id = (int)($this->request->get('id') ?: 0);
            if ($id > 0 && $this->resource->canDelete()) {
                $this->resource->delete($id);
            }
            $this->redirect($this->baseListUrl());
            return;
        }

        // Handle bulk delete (from ACTION_PANEL)
        if ($action === 'delete_bulk' && $this->isPost() && check_bitrix_sessid()) {
            $ids = array_filter(array_map('intval', (array)$this->request->getPost('id')));
            if (!empty($ids)) {
                $this->resource->massDelete($ids);
            }
            $this->redirect($this->baseListUrl());
            return;
        }

        Extension::load(['ui.buttons', 'sidepanel']);

        $APPLICATION->SetTitle($this->resource->getTitle());

        $grid = $this->buildGrid();
        $this->loadData($grid);
        $this->renderToolbar($grid);

        $APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', $grid->getGridComponentParams());
    }

    protected function buildGrid(): Grid
    {
        if ($this->grid) {
            return $this->grid;
        }

        $fields = $this->getVisibleFields();
        $filters = iterator_to_array($this->resource->filters());
        $rowActions = iterator_to_array($this->resource->rowActions());

        $this->grid = new Grid(
            $this->resource->getGridId(),
            $fields,
            $filters,
            $rowActions,
            $this->baseListUrl(),
            $this->resource->getPrimaryKey(),
        );

        $bulkActions = array_filter(
            iterator_to_array($this->resource->bulkActions()),
            fn($a) => $a instanceof BulkAction
        );

        if (!empty($bulkActions)) {
            $this->grid->setBulkActions(array_values($bulkActions));
        }

        return $this->grid;
    }

    protected function loadData(Grid $grid): void
    {
        $dataManagerClass = $this->resource->getDataManagerClass();
        if (!$dataManagerClass) {
            return;
        }

        $context = new GridContext(
            $this->resource,
            $grid->getId(),
            $grid->getFilterId(),
            [],
            [],
            $grid->getPagination()->getPageSize(),
            $grid->getPagination()->getCurrentPage(),
            $grid->getPagination()->getOffset(),
            $grid->getPagination()->getLimit(),
            $this->request,
        );

        $params = (new GridQueryBuilder())->build($this->resource, $context);
        $grid->setTotalCount($dataManagerClass::getCount($params['filter'] ?? []));
        $grid->setRawRows($dataManagerClass::getList($params), $context);
    }

    protected function renderToolbar(Grid $grid): void
    {
        global $APPLICATION;

        // Integrate filter into toolbar (Toolbar::addFilter captures main.ui.filter output)
        if ($filterParams = $grid->getFilterComponentParams()) {
            Toolbar::addFilter($filterParams);
        }

        // "Create" button — opens FormPage in a SidePanel; reloads grid on close
        if ($this->resource->canCreate()) {
            $addUrl = $this->baseFormUrl('add');
            $gridId = $this->resource->getGridId();

            Toolbar::addButton(
                new Button([
                    'color' => Color::SUCCESS,
                    'icon' => Icon::ADD,
                    'text' => 'Создать',
                    'click' => new JsCode(
                        'BX.SidePanel.Instance.open(' . json_encode($addUrl) . ', {
                            events: {
                                onCloseComplete: function() {
                                    var grid = BX.Main.gridManager.getInstanceById(' . json_encode($gridId) . ');
                                    if (grid) { grid.reload(); }
                                }
                            }
                        })'
                    ),
                ]),
                ButtonLocation::AFTER_TITLE
            );
        }

        $APPLICATION->IncludeComponent('bitrix:ui.toolbar', 'admin', []);
    }

    /** @return FieldContract[] */
    protected function getVisibleFields(): array
    {
        $fields = [];
        foreach ($this->resource->indexFields() as $field) {
            if ($field instanceof FieldContract && $field->isVisibleOn(PageType::INDEX)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Base URL for the current resource list — path + `page` param only.
     * Strips action/id/saved/IFRAME so redirects and links always land on the list.
     */
    protected function baseListUrl(): string
    {
        return UrlGenerator::forCurrentRequest($this->request)->indexUrl();
    }

    /** URL for add/edit form — list base + action (+ optional id). */
    protected function baseFormUrl(string $action = 'add', ?int $id = null): string
    {
        $generator = new UrlGenerator($this->baseListUrl());
        return $action === 'add' ? $generator->createUrl() : $generator->editUrl($id);
    }
}
