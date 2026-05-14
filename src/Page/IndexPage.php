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
use MB\Bitrix\AdminKit\Action\MassDeleteAction;
use MB\Bitrix\AdminKit\Component\Notification;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\Performance\ArrayTtlCache;
use MB\Bitrix\AdminKit\Database\Performance\QueryGuard;
use MB\Bitrix\AdminKit\Database\Performance\QueryPerformanceContext;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Bitrix\AdminKit\Support\UrlGenerator;
use MB\Bitrix\AdminKit\Support\AdminString;

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
            $item = $id > 0 ? $this->resource->findItem($id) : null;
            if ($id > 0 && $this->resource->canDelete(new PermissionContext(resource: $this->resource, operation: 'delete', item: $item))) {
                $this->resource->delete($id);
            }
            $this->redirect($this->baseListUrl());
            return;
        }

        if ($this->isPost() && is_string($action) && $this->handleBulkAction($action)) {
            $this->redirect($this->baseListUrl());
            return;
        }

        Extension::load(['ui.buttons', 'sidepanel']);

        $APPLICATION->SetTitle($this->resource->getTitle());

        $grid = $this->buildGrid();
        $this->loadData($grid);
        $this->renderToolbar($grid);
        $this->renderBulkResult();

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
        $this->grid->limitPageSize($this->resource->maxPageSize());

        $bulkActions = array_filter(
            iterator_to_array($this->resource->bulkActions()),
            fn($a) => $a instanceof BulkAction && $a->isVisible()
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

        $params = (new QueryGuard())->guardGridParams((new GridQueryBuilder())->build($this->resource, $context), $context);
        $start = microtime(true);
        $countQueryUsed = false;
        $cacheUsed = false;

        if ($this->resource->useTotalCount($context)) {
            [$count, $cacheUsed] = $this->resolveTotalCount($dataManagerClass, $context, $params['filter'] ?? []);
            $countQueryUsed = !$cacheUsed;
            $grid->setTotalCount($count);
        }

        $result = $dataManagerClass::getList($params);
        $grid->setRawRows($result, $context);
        $this->debugQuery(new QueryPerformanceContext(
            $this->resource,
            $context,
            $params,
            microtime(true) - $start,
            0,
            $countQueryUsed,
            $cacheUsed,
        ));
    }


    /** @param class-string $dataManagerClass @param array<string,mixed> $filter @return array{0:int,1:bool} */
    protected function resolveTotalCount(string $dataManagerClass, GridContext $context, array $filter): array
    {
        $ttl = $this->resource->countCacheTtl($context);
        if ($ttl <= 0) {
            return [(int)$dataManagerClass::getCount($filter), false];
        }

        $key = AdminString::cacheKey('adminkit_count', [
            'module' => 'mb.bitrix.adminkit',
            'resource' => $this->resource::getId(),
            'grid' => $context->gridId,
            'filter' => $filter,
            'user' => $this->currentUserId(),
        ]);
        $cached = ArrayTtlCache::get($key);
        if ($cached !== null) {
            return [(int)$cached, true];
        }

        $count = (int)$dataManagerClass::getCount($filter);
        ArrayTtlCache::set($key, $count, $ttl);

        return [$count, false];
    }

    protected function debugQuery(QueryPerformanceContext $context): void
    {
        if (!$this->isDebugAllowed()) {
            return;
        }

        error_log('AdminKit ORM params: ' . json_encode([
            'resource' => $context->resource::getId(),
            'params' => $context->params,
            'executionTime' => $context->executionTime,
            'rowCount' => $context->rowCount,
            'countQueryUsed' => $context->countQueryUsed,
            'cacheUsed' => $context->cacheUsed,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function isDebugAllowed(): bool
    {
        if (!defined('ADMIN_KIT_DEBUG') || ADMIN_KIT_DEBUG !== true) {
            return false;
        }

        global $USER;
        return is_object($USER) && method_exists($USER, 'IsAdmin') && (bool)$USER->IsAdmin();
    }

    protected function currentUserId(): mixed
    {
        global $USER;
        return is_object($USER) && method_exists($USER, 'GetID') ? $USER->GetID() : null;
    }

    protected function renderToolbar(Grid $grid): void
    {
        global $APPLICATION;

        // Integrate filter into toolbar (Toolbar::addFilter captures main.ui.filter output)
        if ($filterParams = $grid->getFilterComponentParams()) {
            Toolbar::addFilter($filterParams);
        }

        // "Create" button — opens FormPage in a SidePanel; reloads grid on close
        if ($this->resource->canCreate(new PermissionContext(resource: $this->resource, operation: 'create'))) {
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

    protected function renderBulkResult(): void
    {
        $gridId = $this->resource->getGridId();
        $result = $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$gridId] ?? null;
        if (!is_array($result)) {
            return;
        }

        unset($_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$gridId]);

        echo Notification::alert(
            (string)($result['message'] ?? ''),
            ($result['success'] ?? false) ? Notification::TYPE_SUCCESS : Notification::TYPE_WARNING,
        );
    }

    protected function handleBulkAction(string $actionId): bool
    {
        foreach ($this->resource->bulkActions() as $bulkAction) {
            if (!$bulkAction instanceof BulkAction || $bulkAction->getId() !== $actionId) {
                continue;
            }

            $action = $bulkAction->isDelete() && !$bulkAction instanceof MassDeleteAction
                ? new MassDeleteAction($bulkAction->getId(), $bulkAction->getLabel())
                : $bulkAction;

            $ids = array_values(array_filter((array)$this->request->getPost('id'), static fn($id): bool => $id !== null && $id !== ''));
            $context = new BulkOperationContext(
                resource: $this->resource,
                action: $action,
                selectedIds: $ids,
                request: $this->request,
            );

            $guardErrors = (new QueryGuard())->validateBulkOperation($context);
            if ($guardErrors !== []) {
                $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$this->resource->getGridId()] = [
                    'message' => implode(' ', $guardErrors),
                    'success' => false,
                ];
                return true;
            }

            $result = $action->execute($context);
            $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$this->resource->getGridId()] = [
                'message' => $result->message(),
                'success' => $result->isSuccess(),
            ];

            return true;
        }

        return false;
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
