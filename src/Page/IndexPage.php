<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Manager\AssetManager;
use MB\Bitrix\AdminKit\Action\MassDeleteAction;
use MB\Bitrix\AdminKit\Bitrix\Toolbar\ToolbarRenderer;
use MB\Bitrix\AdminKit\Component\Notification;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\IndexPageDefinitionContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
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

class IndexPage extends Page
{
    protected ?Grid $grid = null;

    public function __construct(ResourceContract $resource, mixed $id = null, array $params = [])
    {
        parent::__construct($resource, $id, $params);
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
            $id = GridRowId::normalizeItemId($this->request->get('id'));
            if ($id !== null && $id !== '') {
                $item = $this->resource->findItem($id);
                if ($item !== null && $this->resource->canDelete(new PermissionContext(resource: $this->resource, operation: 'delete', item: $item))) {
                    $this->resource->delete($id);
                }
            }
            $this->redirect($this->baseListUrl());
            return;
        }

        if ($this->isPost()) {
            if ($this->handleInlineEdit()) {
                // Keep normal list rendering flow so main.ui.grid receives HTML for reloadTable().
            }

            $bulkAction = $this->resolveBulkActionId();
            if ($bulkAction !== null) {
                $result = $this->handleBulkAction($bulkAction);

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
            $this->handleExportAction();
            return;
        }

        if ($action === 'export_selected') {
            $this->handleExportAction($this->resolveSelectedIds());
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
        $this->renderToolbar($grid);
        $this->renderBulkResult();

        $APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', $grid->getGridComponentParams());

        if ($this->definition()->grouping() instanceof IndexGrouping) {
            AdminKitJs::renderInit('GridCollapsible', []);
        }
    }

    protected function buildGrid(): Grid
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

        $grouping = $this->definition()->grouping();
        if ($grouping instanceof IndexGrouping) {
            $this->grid->enableCollapsibleRows(true, $this->resolveCollapsibleShiftColumn($grouping, $fields));
            $this->grid->setGroupingAlign($grouping->align());
        }

        return $this->grid;
    }

    protected function loadData(Grid $grid): void
    {
        (new GridDataLoader())->load($this->resource, $grid, $this->request, null, $this->definition());
    }

    protected function renderToolbar(Grid $grid): void
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

    /** @return array{success:bool,message:string}|null */
    protected function handleBulkAction(string $actionId): ?array
    {
        if ($actionId === 'export_selected') {
            $this->handleExportAction($this->resolveSelectedIds());
            return null;
        }

        foreach ($this->bulkActions() as $bulkAction) {
            if (!$bulkAction instanceof BulkAction || $bulkAction->getId() !== $actionId) {
                continue;
            }

            $action = $bulkAction->isDelete() && !$bulkAction instanceof MassDeleteAction
                ? new MassDeleteAction($bulkAction->getId(), $bulkAction->getLabel())
                : $bulkAction;

            $ids = $this->resolveSelectedIds();
            $context = new BulkOperationContext(
                resource: $this->resource,
                action: $action,
                selectedIds: $ids,
                request: $this->request,
            );

            $guardErrors = (new QueryGuard())->validateBulkOperation($context);
            if ($guardErrors !== []) {
                $payload = [
                    'message' => implode(' ', $guardErrors),
                    'success' => false,
                ];
                $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$this->resource->getGridId()] = $payload;

                return $payload;
            }

            $result = $action->execute($context);
            $payload = [
                'message' => $result->message(),
                'success' => $result->isSuccess(),
            ];
            $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$this->resource->getGridId()] = $payload;

            return $payload;
        }

        return null;
    }


    /** @return iterable<FieldContract> */
    protected function fields(): iterable
    {
        return $this->resource->indexFields();
    }

    protected function grouping(): ?IndexGrouping
    {
        return method_exists($this->resource, 'indexGrouping') ? $this->resource->indexGrouping() : null;
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
    protected function bulkActions(): iterable
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

    protected function resolveBulkActionId(): ?string
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
    protected function resolveSelectedIds(): array
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

    protected function isAjaxRequest(): bool
    {
        return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    protected function isLegacyBulkAjaxRequest(): bool
    {
        return (string)($_POST['adminkit_bulk_action'] ?? '') !== '';
    }

    protected function handleInlineEdit(): bool
    {
        $actionKey = 'action_button_' . $this->resource->getGridId();
        $action = (string)($_POST[$actionKey] ?? '');
        $rows = $_POST['FIELDS'] ?? null;

        if ($action !== 'edit' || !is_array($rows)) {
            return false;
        }

        $messages = [];

        foreach ($rows as $id => $payload) {
            if (!is_array($payload)) {
                continue;
            }

            $messages = array_merge($messages, $this->saveInlineRow($id, $this->sanitizeInlinePayload($payload)));
        }

        if ($messages !== []) {
            $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$this->resource->getGridId()] = [
                'message' => implode(' ', $messages),
                'success' => false,
            ];
        }

        return true;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<int,string>
     */
    protected function saveInlineRow(mixed $id, array $payload): array
    {
        if (GridRowId::isGroupId($id)) {
            return [];
        }
        $id = GridRowId::normalizeItemId($id);
        if ($id === null || $id === '') {
            return [];
        }
        $idLabel = (string)$id;
        $oldRow = $this->resource->findItem($id);
        if (!is_array($oldRow)) {
            return ["Row {$idLabel}: item was not found."];
        }

        $permission = new PermissionContext(resource: $this->resource, operation: 'update', item: $oldRow);
        if (!$this->resource->canUpdate($permission)) {
            return ["Row {$idLabel}: update permission denied."];
        }

        $merged = array_merge($oldRow, $payload);
        $fields = $this->resolveInlineFields(array_keys($payload));
        $formData = (new DataPipeline())->process($fields, $merged);

        $context = new DbOperationContext(
            resource: $this->resource,
            operation: 'update',
            itemId: $id,
            oldData: $oldRow,
            newData: $formData->validated(),
            rawData: $formData->raw(),
            normalizedData: $formData->normalized(),
            validatedData: $formData->validated(),
            request: $this->request,
        );

        $this->resource->beforeValidate($formData, $context);
        if ($formData->hasErrors()) {
            return $this->flattenInlineErrors($id, $formData->errors());
        }
        $this->resource->afterValidate($formData, $context);

        $result = $this->resource->updateItemResult($id, $formData, $context);
        if (!$result->isSuccess()) {
            $errors = $result->errors();
            if ($errors === []) {
                return ["Row {$idLabel}: update failed."];
            }

            return array_map(static fn (string $error): string => "Row {$idLabel}: {$error}", $errors);
        }

        return [];
    }

    /**
     * @param array<int,string> $editedColumns
     * @return array<int,FieldContract>
     */
    protected function resolveInlineFields(array $editedColumns): array
    {
        $allowed = array_flip($editedColumns);
        $result = [];
        $known = [];

        foreach ($this->resource->formFields() as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }
            $known[$field->getColumn()] = $field;
        }

        foreach ($this->fields() as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }
            $known[$field->getColumn()] ??= $field;
        }

        foreach ($known as $column => $field) {
            if (!isset($allowed[$column])) {
                continue;
            }
            if ($field->isReadOnly()) {
                continue;
            }
            $result[] = $field;
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    protected function sanitizeInlinePayload(array $payload): array
    {
        $result = [];
        foreach ($payload as $key => $value) {
            $column = (string)$key;
            if ($column === '' || $column[0] === '~' || $column[0] === '=') {
                continue;
            }
            $result[$column] = $value;
        }

        return $result;
    }

    /**
     * @param array<string,array<int,string>> $errors
     * @return array<int,string>
     */
    protected function flattenInlineErrors(mixed $id, array $errors): array
    {
        $idLabel = (string)$id;
        $messages = [];
        foreach ($errors as $column => $columnMessages) {
            foreach ($columnMessages as $message) {
                $template = $this->message('MB_ADMIN_KIT_INDEX_ROW_ERROR_TEMPLATE', 'Row #ID#, #COLUMN#: #MESSAGE#');
                $messages[] = str_replace(['#ID#', '#COLUMN#', '#MESSAGE#'], [$idLabel, (string)$column, (string)$message], $template);
            }
        }

        return $messages;
    }

    /** @param array<string,mixed> $payload */
    protected function sendJson(array $payload): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        die();
    }

    /** @param array<int,mixed>|null $selectedIdsOverride */
    protected function handleExportAction(?array $selectedIdsOverride = null): void
    {
        $grid = $this->buildGrid();
        $context = (new GridDataLoader())->makeContext($this->resource, $grid, $this->request);
        $selectedIds = $selectedIdsOverride ?? [];
        $queryParams = (new GridQueryBuilder())->build($this->resource, $context, $this->definition());
        $filter = $selectedIds === [] ? (is_array($queryParams['filter'] ?? null) ? $queryParams['filter'] : []) : [];

        $result = ExportAction::make()->execute(new ExportContext(
            resource: $this->resource,
            selectedIds: $selectedIds,
            filter: $filter,
            userId: $this->currentUserId(),
            format: 'csv',
            gridContext: $context,
        ));

        if (!$result->isSuccess()) {
            $message = trim(implode(' ', array_filter($result->errors, static fn (mixed $error): bool => is_string($error) && trim($error) !== '')));
            $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$this->resource->getGridId()] = [
                'message' => $message !== '' ? $message : 'Ошибка экспорта.',
                'success' => false,
            ];
            $this->redirect($this->baseListUrl());
            return;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $result->contentType);
        header('Content-Disposition: attachment; filename="' . addslashes($result->filename) . '"');
        echo $result->content;
        die();
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

    protected function currentUserId(): mixed
    {
        global $USER;

        return is_object($USER) && method_exists($USER, 'GetID') ? $USER->GetID() : null;
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

    private function message(string $key, string $fallback): string
    {
        if (class_exists(Loc::class)) {
            Loc::loadMessages(__FILE__);
            return (string)(Loc::getMessage($key) ?: $fallback);
        }

        return $fallback;
    }

}
