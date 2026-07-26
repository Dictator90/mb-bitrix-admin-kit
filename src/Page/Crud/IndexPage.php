<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Crud;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Bitrix\Toolbar\ToolbarRenderer;
use MB\Bitrix\AdminKit\Component\Notification;
use MB\Bitrix\AdminKit\Contracts\Action\BulkPanelItemContract;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\IndexPageDefinitionContract;
use MB\Bitrix\AdminKit\Contracts\Page\IndexPageContract;
use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;
use MB\Bitrix\AdminKit\Grid\Grid;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridDataLoader;
use MB\Bitrix\AdminKit\Grid\GridSettings;
use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;
use MB\Bitrix\AdminKit\Grid\Row\GridRowId;
use MB\Bitrix\AdminKit\Manager\AssetManager;
use MB\Bitrix\AdminKit\Page\Crud\Handlers\IndexBulkActionHandler;
use MB\Bitrix\AdminKit\Page\Crud\Handlers\IndexDeleteHandler;
use MB\Bitrix\AdminKit\Page\Crud\Handlers\IndexExportHandler;
use MB\Bitrix\AdminKit\Page\Crud\Handlers\IndexInlineEditHandler;
use MB\Bitrix\AdminKit\Page\Crud\Handlers\IndexRowSortHandler;
use MB\Bitrix\AdminKit\Page\CrudPage;
use MB\Bitrix\AdminKit\Page\IndexPageDefinition;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\AdminKitJs;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;
use MB\Bitrix\AdminKit\Support\UrlGenerator;

class IndexPage extends CrudPage implements IndexPageContract
{
    protected ?Grid $grid = null;

    public static function pageName(): string
    {
        return 'index';
    }

    protected static function defaultPageType(): PageType
    {
        return PageType::INDEX;
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

        if ($action === 'rowsort' && $this->isPost() && check_bitrix_sessid()) {
            $result = (new IndexRowSortHandler())->handle($this);
            $this->sendJson($result);

            return;
        }

        if ($this->isPost()) {
            if ((new IndexInlineEditHandler())->handle($this)) {
                // Keep normal list rendering flow so main.ui.grid receives HTML for reloadTable().
            }

            $bulkAction = $this->resolveBulkActionId();
            if ($bulkAction !== null) {
                $result = (new IndexBulkActionHandler())->handle($this, $bulkAction);

                if ($result !== null && $this->isBulkAjaxRequest()) {
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

        if ($action === 'export' || $action === 'export_selected') {
            if (method_exists($this->resource(), 'exportEnabled') && !$this->resource()->exportEnabled()) {
                $this->redirect($this->baseListUrl());

                return;
            }

            $selectedIds = $action === 'export_selected' ? $this->resolveSelectedIds() : null;
            (new IndexExportHandler())->handle($this, $selectedIds);

            return;
        }

        (new AssetManager())
            ->forGrid()
            ->forSidePanel()
            ->addExtensions(['ui.notification', 'ui.alerts'])
            ->load();

        $APPLICATION->SetTitle($this->resource()->getTitle());

        $grid = $this->buildGrid();
        $this->loadData($grid);
        $this->renderIndexToolbar($grid);
        $this->renderBulkResult();

        $APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', $grid->getGridComponentParams());

        if ($this->grouping() instanceof IndexGrouping) {
            AdminKitJs::renderInit('GridCollapsible', []);
        }

        if ($grid->settings()->allowRowsSort && $this->resource()->sortField() !== null) {
            AdminKitJs::renderInit('GridRowSort', [
                'gridId' => $grid->getId(),
                'url' => $this->baseListUrl(),
            ]);
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
            $this->resource()->getGridId(),
            $fields,
            $filters,
            $rowActions,
            $this->baseListUrl(),
            $this->resourcePrimaryKey(),
        );
        $this->grid->setSettings(GridSettings::fromResource($this->resource()));
        $this->grid->limitPageSize($this->resource()->maxPageSize());

        if (!$this->resource()->showPagination()) {
            $this->grid->showAllRecords($this->resource()->maxPageSize());
        }

        $bulkActions = array_filter(
            iterator_to_array($this->bulkActions()),
            fn ($a) => $a instanceof BulkPanelItemContract && $a->isVisible()
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
        if (!$this->resource() instanceof DataManagerResourceContract) {
            return;
        }

        (new GridDataLoader())->load($this->resource(), $grid, $this->request, null, $this->definition());
    }

    protected function renderIndexToolbar(Grid $grid): void
    {
        (new ToolbarRenderer())->render($this->resource(), $grid, $this->baseFormUrl('add'));
    }

    protected function renderBulkResult(): void
    {
        $gridId = $this->resource()->getGridId();
        $result = $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$gridId] ?? null;

        if (!is_array($result)) {
            return;
        }

        unset($_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$gridId]);

        echo Notification::alert(
            $this->formatBulkResultMessage($result),
            ($result['success'] ?? false) ? Notification::TYPE_SUCCESS : Notification::TYPE_WARNING,
        );
    }


    /** @param array<string,mixed> $result */
    protected function formatBulkResultMessage(array $result): string
    {
        $parts = [(string)($result['message'] ?? '')];

        foreach (['errors', 'warnings', 'skipped'] as $key) {
            if (!isset($result[$key]) || !is_array($result[$key])) {
                continue;
            }

            foreach (array_slice($result[$key], 0, 10, true) as $id => $messages) {
                $messages = is_array($messages) ? implode(', ', array_map('strval', $messages)) : (string)$messages;
                if ($messages !== '') {
                    $parts[] = '#' . (string)$id . ': ' . $messages;
                }
            }
        }

        return trim(implode("\n", array_filter($parts, static fn (string $part): bool => $part !== '')));
    }

    /** @return iterable<FieldContract> */
    public function fields(): iterable
    {
        return $this->resource()->indexFields();
    }

    /**
     * Index grouping for this page. Override to change or disable resource-level grouping.
     */
    protected function grouping(): ?IndexGrouping
    {
        return $this->resource()->indexGrouping();
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
        return $this->resource()->filters();
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\ActionContract> */
    protected function rowActions(): iterable
    {
        return $this->resource()->rowActions();
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\ActionContract> */
    public function bulkActions(): iterable
    {
        $actions = iterator_to_array($this->resource()->bulkActions());

        // Экспорт выбранных добавляем только если экспорт включён у ресурса (по умолчанию выключен).
        if (method_exists($this->resource(), 'exportEnabled') && !$this->resource()->exportEnabled()) {
            return $actions;
        }

        foreach ($actions as $action) {
            if ($action instanceof BulkAction && $action->getId() === 'export_selected') {
                return $actions;
            }
        }

        $actions[] = BulkAction::make('export_selected', $this->message('MB_ADMIN_KIT_INDEX_EXPORT_SELECTED_CSV', 'Export selected CSV'))
            ->clientHandler('exportSelected');

        return $actions;
    }

    /** @return array<string,string> */
    protected function defaultSort(): array
    {
        return $this->resource()->defaultSort();
    }

    /** @return array<string,mixed> */
    protected function defaultFilter(): array
    {
        return $this->resource()->defaultFilter();
    }

    /** @return array<int,string> */
    protected function defaultSelect(): array
    {
        return $this->resource()->defaultSelect();
    }

    /** @return array<int,mixed> */
    protected function runtimeFields(): array
    {
        return $this->resource()->runtimeFields();
    }

    /** @return array<int,string> */
    protected function indexSelect(GridContext $context): array
    {
        return $this->resource()->indexSelect($context);
    }

    /** @return array<string,mixed> */
    protected function indexFilter(GridContext $context): array
    {
        return $this->resource()->indexFilter($context);
    }

    /** @return array<string,string> */
    protected function indexOrder(GridContext $context): array
    {
        return $this->resource()->indexOrder($context);
    }

    /** @return array<int,mixed> */
    protected function indexRuntime(GridContext $context): array
    {
        return $this->resource()->indexRuntime($context);
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    protected function beforeIndexQueryParams(array $params, GridContext $context): array
    {
        return $this->resource()->beforeIndexQueryParams($params, $context);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    protected function afterIndexRows(array $rows, GridContext $context): array
    {
        return $this->resource()->afterIndexRows($rows, $context);
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    protected function mapIndexRow(array $row, GridContext $context): array
    {
        return $this->resource()->mapIndexRow($row, $context);
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    protected function modifyIndexParams(array $params, GridContext $context): array
    {
        return $this->resource()->modifyIndexParams($params, $context);
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

        $panelActionKey = 'action_button_' . $this->resource()->getGridId();
        $panelAction = (string)($_POST[$panelActionKey] ?? '');
        if ($panelAction !== '') {
            return $panelAction;
        }

        $legacy = (string)($_POST['action'] ?? '');

        return $legacy !== '' ? $legacy : null;
    }

    public function isForAllRowsSelected(): bool
    {
        $key = 'action_all_rows_' . $this->resource()->getGridId();

        return (string)($_POST[$key] ?? '') === 'Y';
    }

    /** @return array<int,mixed> */
    public function resolveSelectedIds(): array
    {
        $sources = [
            $_POST['id'] ?? null,
            $_POST['ID'] ?? null,
            $_POST['ids'] ?? null,
            $_POST['rows'] ?? null,
            $_POST[$this->resourcePrimaryKey()] ?? null,
            $_POST[strtoupper($this->resourcePrimaryKey())] ?? null,
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

    /**
     * @deprecated kept for backward compatibility with legacy IndexPage overrides/tests.
     *
     * @param array<string,mixed> $payload
     * @return array<int,string>
     */
    protected function saveInlineRow(mixed $id, array $payload): array
    {
        return (new IndexInlineEditHandler())->saveInlineRow($this, $id, $payload);
    }

    public function isBulkAjaxRequest(): bool
    {
        return (string)($_POST['adminkit_bulk_ajax'] ?? '') === 'Y'
            || (string)($_POST['adminkit_bulk_action'] ?? '') !== '';
    }

    /** @deprecated use isBulkAjaxRequest() */
    public function isLegacyBulkAjaxRequest(): bool
    {
        return $this->isBulkAjaxRequest();
    }

    protected function canViewIndex(): bool
    {
        if ($this->resource()->canView(new PermissionContext(resource: $this->resource(), operation: 'view'))) {
            return true;
        }

        $this->renderPermissionError(
            $this->message('MB_ADMIN_KIT_INDEX_ERR_CANNOT_VIEW', 'Insufficient permissions to view this section.'),
        );

        return false;
    }

    protected function rejectPostWithoutSessid(): void
    {
        $message = $this->message(
            'MB_ADMIN_KIT_INDEX_SESSION_EXPIRED',
            'Session expired. Refresh the page and try again.',
        );

        if ($this->isAjaxRequest() || $this->isLegacyBulkAjaxRequest()) {
            $this->sendJson([
                'success' => false,
                'message' => $message,
            ]);
        }

        $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$this->resource()->getGridId()] = [
            'message' => $message,
            'success' => false,
        ];

        if ($this->isPost()) {
            $this->redirect($this->baseListUrl());
        }
    }

    public function message(string $key, string $fallback): string
    {
        return LocalizedMessage::get(__FILE__, $key, $fallback);
    }

}
