<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Crud;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Manager\AssetManager;
use MB\Bitrix\AdminKit\Action\MassDeleteAction;
use MB\Bitrix\AdminKit\Bitrix\Toolbar\ToolbarRenderer;
use MB\Bitrix\AdminKit\Component\Notification;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\IndexPageDefinitionContract;
use MB\Bitrix\AdminKit\Contracts\Page\IndexPageContract;
use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourcePersistenceContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Page\Crud\Handlers\IndexBulkActionHandler;
use MB\Bitrix\AdminKit\Page\Crud\Handlers\IndexDeleteHandler;
use MB\Bitrix\AdminKit\Page\Crud\Handlers\IndexExportHandler;
use MB\Bitrix\AdminKit\Page\Crud\Handlers\IndexInlineEditHandler;
use MB\Bitrix\AdminKit\Page\CrudPage;
use MB\Bitrix\AdminKit\Page\IndexPageDefinition;
use MB\Bitrix\AdminKit\Database\BulkOperationContext;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Database\Performance\QueryGuard;
use MB\Bitrix\AdminKit\Export\ExportAction;
use MB\Bitrix\AdminKit\Export\ExportContext;
use MB\Bitrix\AdminKit\Form\DataPipeline;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridDataLoader;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;
use MB\Bitrix\AdminKit\Grid\Row\GridRowId;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\AdminKitJs;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Bitrix\AdminKit\Support\UrlGenerator;

class IndexPage extends CrudPage implements IndexPageContract
{
    protected ?Grid $grid = null;

    public function __construct(?ResourceContract $resource = null, mixed $id = null, array $params = [])
    {
        parent::__construct($resource, $id, $params);
        $this->pageType = PageType::INDEX;
    }

    public static function pageName(): string
    {
        return 'index';
    }

    public function definition(): IndexPageDefinitionContract
    {
        return new IndexPageDefinition([
            'fields' => fn (): iterable => $this->fields(),
            'grouping' => fn (): ?IndexGrouping => $this->grouping(),
            'filters' => fn (): iterable => $this->filters(),
            'rowActions' => fn (): iterable => $this->rowActions(),
            'bulkActions' => fn (): iterable => $this->bulkActions(),
            'defaultSort' => fn (): array => $this->defaultSort(),
            'defaultFilter' => fn (): array => $this->defaultFilter(),
            'defaultSelect' => fn (): array => $this->defaultSelect(),
            'runtimeFields' => fn (): array => $this->runtimeFields(),
            'indexSelect' => fn (GridContext $context): array => $this->indexSelect($context),
            'indexFilter' => fn (GridContext $context): array => $this->indexFilter($context),
            'indexOrder' => fn (GridContext $context): array => $this->indexOrder($context),
            'indexRuntime' => fn (GridContext $context): array => $this->indexRuntime($context),
            'beforeIndexQueryParams' => fn (array $params, GridContext $context): array => $this->beforeIndexQueryParams($params, $context),
            'afterIndexRows' => fn (array $rows, GridContext $context): array => $this->afterIndexRows($rows, $context),
            'mapIndexRow' => fn (array $row, GridContext $context): array => $this->mapIndexRow($row, $context),
            'modifyIndexParams' => fn (array $params, GridContext $context): array => $this->modifyIndexParams($params, $context),
        ]);
    }

    public function render(): void
    {
        global $APPLICATION;

        $action = $this->request->get('action') ?: $this->request->getPost('action');

        if ($this->isPost() && !check_bitrix_sessid()) {
            $this->rejectPostWithoutSessid();

            return;
        }

        if ($action === 'delete' && check_bitrix_sessid()) {
            if ((new IndexDeleteHandler())->handle($this)) {
                $this->redirect($this->baseListUrl());

                return;
            }
        }

        if ($this->isPost()) {
            if ((new IndexInlineEditHandler())->handle($this)) {
                // Keep normal list rendering flow so main.ui.grid receives HTML for reloadTable().
            }

            $bulkAction = $this->resolveBulkActionId();
            if ($bulkAction !== null) {
                $result = (new IndexBulkActionHandler())->handle($this, $bulkAction);

                if ($result !== null && $this->isLegacyBulkAjaxRequest()) {
                    $this->sendJson($result);
                    return;
                }

                if ($result !== null && !$this->isAjaxRequest()) {
                    $this->redirect($this->baseListUrl());
                    return;
                }
            }
        }

        if (!$this->canViewIndex()) {
            return;
        }

        if ($action === 'export') {
            (new IndexExportHandler())->handle($this);

            return;
        }

        if ($action === 'export_selected') {
            (new IndexExportHandler())->handle($this, $this->resolveSelectedIds());

            return;
        }

        (new AssetManager())
            ->forGrid()
            ->forSidePanel()
            ->addExtensions(['ui.notification', 'ui.alerts'])
            ->load();

        $APPLICATION->SetTitle($this->resource->getTitle());

        $grid = $this->buildGrid();
        $this->loadData($grid);
        $this->renderIndexToolbar($grid);
        $this->renderBulkResult();

        $APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', $grid->getGridComponentParams());

        if ($this->grouping() instanceof IndexGrouping) {
            AdminKitJs::renderInit('GridCollapsible', []);
        }
    }

    public function buildGrid(): Grid
    {
        if ($this->grid) {
            return $this->grid;
        }

        $fields = $this->getVisibleFields();
        $filters = iterator_to_array($this->filters());
        $rowActions = iterator_to_array($this->rowActions());

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
            iterator_to_array($this->bulkActions()),
            fn ($a) => $a instanceof BulkAction && $a->isVisible()
        );

        if (!empty($bulkActions)) {
            $this->grid->setBulkActions(array_values($bulkActions));
        }

        $grouping = $this->grouping();
        if ($grouping instanceof IndexGrouping) {
            $this->grid->enableCollapsibleRows(true, $this->resolveCollapsibleShiftColumn($grouping, $fields));
            $this->grid->setGroupingAlign($grouping->align());
        }

        return $this->grid;
    }

    protected function loadData(Grid $grid): void
    {
        if (!$this->resource instanceof DataManagerResourceContract) {
            return;
        }

        (new GridDataLoader())->load($this->resource, $grid, $this->request, null, $this->definition());
    }

    protected function renderIndexToolbar(Grid $grid): void
    {
        (new ToolbarRenderer())->render($this->resource, $grid, $this->baseFormUrl('add'));
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

    /** @return iterable<FieldContract> */
    public function fields(): iterable
    {
        return $this->resource->indexFields();
    }

    /**
     * Index grouping for this page. Override to change or disable resource-level grouping.
     */
    protected function grouping(): ?IndexGrouping
    {
        return $this->resource->indexGrouping();
    }

    public function indexGrouping(): ?IndexGrouping
    {
        return $this->grouping();
    }

    /**
     * @param FieldContract[] $fields
     */
    protected function resolveCollapsibleShiftColumn(IndexGrouping $grouping, array $fields): ?string
    {
        $labelColumn = $grouping->labelColumn();
        if ($labelColumn !== null && $labelColumn !== '') {
            return $labelColumn;
        }

        $label = $grouping->label();
        if (is_string($label) && $label !== '') {
            return $label;
        }

        foreach ($fields as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }
            if (method_exists($field, 'isComputed') && $field->isComputed()) {
                continue;
            }
            if ($field->getColumn() === 'NAME' || $field->getColumn() === 'TITLE') {
                return $field->getColumn();
            }
        }

        foreach ($fields as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }
            if (method_exists($field, 'isComputed') && $field->isComputed()) {
                continue;
            }

            return $field->getColumn();
        }

        return null;
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\FilterContract> */
    protected function filters(): iterable
    {
        return $this->resource->filters();
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\ActionContract> */
    protected function rowActions(): iterable
    {
        return $this->resource->rowActions();
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\ActionContract> */
    public function bulkActions(): iterable
    {
        $actions = iterator_to_array($this->resource->bulkActions());
        foreach ($actions as $action) {
            if ($action instanceof BulkAction && $action->getId() === 'export_selected') {
                return $actions;
            }
        }

        $actions[] = BulkAction::make('export_selected', 'Экспорт выбранных CSV');

        return $actions;
    }

    /** @return array<string,string> */
    protected function defaultSort(): array
    {
        return $this->resource->defaultSort();
    }

    /** @return array<string,mixed> */
    protected function defaultFilter(): array
    {
        return $this->resource->defaultFilter();
    }

    /** @return array<int,string> */
    protected function defaultSelect(): array
    {
        return $this->resource->defaultSelect();
    }

    /** @return array<int,mixed> */
    protected function runtimeFields(): array
    {
        return $this->resource->runtimeFields();
    }

    /** @return array<int,string> */
    protected function indexSelect(GridContext $context): array
    {
        return $this->resource->indexSelect($context);
    }

    /** @return array<string,mixed> */
    protected function indexFilter(GridContext $context): array
    {
        return $this->resource->indexFilter($context);
    }

    /** @return array<string,string> */
    protected function indexOrder(GridContext $context): array
    {
        return $this->resource->indexOrder($context);
    }

    /** @return array<int,mixed> */
    protected function indexRuntime(GridContext $context): array
    {
        return $this->resource->indexRuntime($context);
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    protected function beforeIndexQueryParams(array $params, GridContext $context): array
    {
        return $this->resource->beforeIndexQueryParams($params, $context);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    protected function afterIndexRows(array $rows, GridContext $context): array
    {
        return $this->resource->afterIndexRows($rows, $context);
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    protected function mapIndexRow(array $row, GridContext $context): array
    {
        return $this->resource->mapIndexRow($row, $context);
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    protected function modifyIndexParams(array $params, GridContext $context): array
    {
        return $this->resource->modifyIndexParams($params, $context);
    }

    /** @return FieldContract[] */
    protected function getVisibleFields(): array
    {
        $fields = [];
        foreach ($this->fields() as $field) {
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
    public function baseListUrl(): string
    {
        return UrlGenerator::forCurrentRequest($this->request)->indexUrl();
    }

    /** URL for add/edit form — list base + action (+ optional id). */
    protected function baseFormUrl(string $action = 'add', ?int $id = null): string
    {
        $generator = new UrlGenerator($this->baseListUrl());
        return $action === 'add' ? $generator->createUrl() : $generator->editUrl($id);
    }

    public function resolveBulkActionId(): ?string
    {
        $explicit = (string)$this->request->getPost('adminkit_bulk_action');
        if ($explicit !== '') {
            return $explicit;
        }

        $controlsAction = $_POST['controls']['group_action'] ?? null;
        if (is_string($controlsAction) && $controlsAction !== '' && $controlsAction !== 'default') {
            return $controlsAction;
        }

        $panelActionKey = 'action_button_' . $this->resource->getGridId();
        $panelAction = (string)($_POST[$panelActionKey] ?? '');
        if ($panelAction !== '') {
            return $panelAction;
        }

        $legacy = (string)($_POST['action'] ?? '');

        return $legacy !== '' ? $legacy : null;
    }

    /** @return array<int,mixed> */
    public function resolveSelectedIds(): array
    {
        $sources = [
            $_POST['id'] ?? null,
            $_POST['ID'] ?? null,
            $_POST['rows'] ?? null,
            $_POST[$this->resource->getPrimaryKey()] ?? null,
            $_POST[strtoupper($this->resource->getPrimaryKey())] ?? null,
        ];

        foreach ($sources as $candidate) {
            $ids = array_values(array_filter((array)$candidate, static fn ($id): bool => $id !== null && $id !== ''));
            if ($ids !== []) {
                return $this->normalizeSelectedIds($ids);
            }
        }

        return [];
    }

    /**
     * @param array<int,mixed> $ids
     * @return array<int,mixed>
     */
    protected function normalizeSelectedIds(array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            $normalized = GridRowId::normalizeItemId($id);
            if ($normalized === null || $normalized === '') {
                continue;
            }
            $result[] = $normalized;
        }

        return $result;
    }

    public function isLegacyBulkAjaxRequest(): bool
    {
        return (string)($_POST['adminkit_bulk_action'] ?? '') !== '';
    }

    protected function canViewIndex(): bool
    {
        if ($this->resource->canView(new PermissionContext(resource: $this->resource, operation: 'view'))) {
            return true;
        }

        Extension::load(['ui.alerts']);
        echo Notification::alert(
            $this->message('MB_ADMIN_KIT_INDEX_ERR_CANNOT_VIEW', 'Недостаточно прав для просмотра раздела.'),
            Notification::TYPE_WARNING,
        );

        return false;
    }

    protected function rejectPostWithoutSessid(): void
    {
        $message = $this->message(
            'MB_ADMIN_KIT_INDEX_SESSION_EXPIRED',
            'Сессия истекла. Обновите страницу и повторите действие.',
        );

        if ($this->isAjaxRequest() || $this->isLegacyBulkAjaxRequest()) {
            $this->sendJson([
                'success' => false,
                'message' => $message,
            ]);
        }

        $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$this->resource->getGridId()] = [
            'message' => $message,
            'success' => false,
        ];

        if ($this->isPost()) {
            $this->redirect($this->baseListUrl());
        }
    }

    public function message(string $key, string $fallback): string
    {
        if (class_exists(Loc::class)) {
            Loc::loadMessages(__FILE__);
            return (string)(Loc::getMessage($key) ?: $fallback);
        }

        return $fallback;
    }

}
