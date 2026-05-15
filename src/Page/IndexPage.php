<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Extension;
use MB\Bitrix\AdminKit\Action\BulkAction;
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
use MB\Bitrix\AdminKit\Import\CsvImporter;
use MB\Bitrix\AdminKit\Import\ImportAction;
use MB\Bitrix\AdminKit\Import\ImportContext;
use MB\Bitrix\AdminKit\Import\ImportResult;
use MB\Bitrix\AdminKit\Manager\SidePanelAdapter;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\AdminString;
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

        // Handle single-row delete (from RowAction)
        $action = $this->request->get('action') ?: $this->request->getPost('action');

        if ($action === 'export') {
            $this->handleExportAction();
            return;
        }

        if ($action === 'export_selected') {
            $this->handleExportAction($this->resolveSelectedIds());
            return;
        }

        if ($action === 'delete' && check_bitrix_sessid()) {
            $id = (int)($this->request->get('id') ?: 0);
            $item = $id > 0 ? $this->resource->findItem($id) : null;
            if ($id > 0 && $this->resource->canDelete(new PermissionContext(resource: $this->resource, operation: 'delete', item: $item))) {
                $this->resource->delete($id);
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

        Extension::load(['ui.buttons', 'sidepanel', 'ui.notification', 'ui.alerts']);

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
     * Base URL for the current resource list РІР‚вЂќ path + `page` param only.
     * Strips action/id/saved/IFRAME so redirects and links always land on the list.
     */
    protected function baseListUrl(): string
    {
        return UrlGenerator::forCurrentRequest($this->request)->indexUrl();
    }

    /** URL for add/edit form РІР‚вЂќ list base + action (+ optional id). */
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
                return $ids;
            }
        }

        return [];
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

            $messages = array_merge($messages, $this->saveInlineRow((string)$id, $this->sanitizeInlinePayload($payload)));
        }

        if ($messages !== []) {
            $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$this->resource->getGridId()] = [
                'message' => implode(' ', $messages),
                'success' => false,
            ];
        }

        return true;
    }

    /** @param array<string,mixed> $payload @return array<int,string> */
    /**
     * @param array<string,mixed> $payload
     * @return array<int,string>
     */
    protected function saveInlineRow(string $id, array $payload): array
    {
        $oldRow = $this->resource->findItem($id);
        if (!is_array($oldRow)) {
            return ["Row {$id}: item was not found."];
        }

        $permission = new PermissionContext(resource: $this->resource, operation: 'update', item: $oldRow);
        if (!$this->resource->canUpdate($permission)) {
            return ["Row {$id}: update permission denied."];
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
                return ["Row {$id}: update failed."];
            }

            return array_map(static fn (string $error): string => "Row {$id}: {$error}", $errors);
        }

        return [];
    }

    /** @param array<int,string> $editedColumns @return array<int,FieldContract> */
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

    /** @param array<string,mixed> $payload @return array<string,mixed> */
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

    /** @param array<string,array<int,string>> $errors @return array<int,string> */
    /**
     * @param array<string,array<int,string>> $errors
     * @return array<int,string>
     */
    protected function flattenInlineErrors(string $id, array $errors): array
    {
        $messages = [];
        foreach ($errors as $column => $columnMessages) {
            foreach ($columnMessages as $message) {
                $template = $this->message('MB_ADMIN_KIT_INDEX_ROW_ERROR_TEMPLATE', 'Row #ID#, #COLUMN#: #MESSAGE#');
                $messages[] = str_replace(['#ID#', '#COLUMN#', '#MESSAGE#'], [$id, (string)$column, (string)$message], $template);
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

    protected function handleImportAction(): void
    {
        global $APPLICATION;

        if ($this->isPost() && (string)$this->request->getPost('adminkit_import_step') !== '') {
            $this->handleImportStep();
            return;
        }

        if (!$this->isSidePanelMode()) {
            $this->openImportInSidePanel();
            return;
        }

        Extension::load(['ui', 'ui.layout-form', 'ui.buttons', 'ui.hint', 'ui.stepprocessing', 'sidepanel']);
        $APPLICATION->SetTitle($this->resource->getTitle() . ': Импорт CSV');
        echo $this->renderImportForm();
    }

    protected function renderImportForm(): string
    {
        $actionUrl = (new UrlGenerator($this->baseListUrl()))->actionUrl('import');
        $actionUrl = (new UrlGenerator($actionUrl))->with(['IFRAME' => 'Y']);
        $formId = htmlspecialcharsbx(AdminString::id('adminkit_import', $this->resource->getGridId()));
        $sessid = function_exists('bitrix_sessid') ? (string)bitrix_sessid() : '';
        $actionUrlJs = (string)json_encode($actionUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $sessidJs = (string)json_encode($sessid, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return <<<HTML
<div class="ui-form ui-form-section adminkit-import">
    <form id="{$formId}" method="post" enctype="multipart/form-data">
        <div class="ui-form-row" data-field-column="import_file">
            <div class="ui-form-label"><div class="ui-ctl-label-text">CSV файл <span class="ui-ctl-required">*</span></div></div>
            <div class="ui-form-content">
                <div class="ui-ctl ui-ctl-textbox">
                    <input class="ui-ctl-element" type="file" name="import_file" accept=".csv,text/csv" required>
                </div>
            </div>
        </div>
        <div class="ui-form-row" data-field-column="import_mode">
            <div class="ui-form-label"><div class="ui-ctl-label-text">Режим</div></div>
            <div class="ui-form-content">
                <div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
                    <div class="ui-ctl-after ui-ctl-icon-angle"></div>
                    <select class="ui-ctl-element" name="import_mode">
                        <option value="create">create</option>
                        <option value="update">update</option>
                        <option value="upsert">upsert</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="ui-form-row" data-field-column="import_key_field">
            <div class="ui-form-label"><div class="ui-ctl-label-text">Ключевое поле (для update/upsert)</div></div>
            <div class="ui-form-content">
                <div class="ui-ctl ui-ctl-textbox">
                    <input class="ui-ctl-element" type="text" name="import_key_field" value="ID">
                </div>
            </div>
        </div>
        <div id="{$formId}_stages" class="ui-form-row" style="display:none" data-field-column="import_stages">
            <div class="ui-form-label"><div class="ui-ctl-label-text">Этапы</div></div>
            <div class="ui-form-content">
                <div id="{$formId}_stage_parse">1. Разбор файла</div>
                <div id="{$formId}_stage_validate">2. Валидация</div>
                <div id="{$formId}_stage_import">3. Импорт</div>
            </div>
        </div>
        <div class="ui-button-panel adminkit-button-panel">
            <button type="button" class="ui-btn ui-btn-success" id="{$formId}_start">Импортировать</button>
            <button type="button" class="ui-btn ui-btn-link" onclick="window.top.BX.SidePanel.Instance.getTopSlider().close()">Отмена</button>
        </div>
    </form>
</div>
<script>
BX.ready(function() {
    var form = document.getElementById({$this->jsonString($formId)});
    var stagesWrap = document.getElementById({$this->jsonString($formId . '_stages')});
    var startButton = document.getElementById({$this->jsonString($formId . '_start')});
    if (!form || !startButton) {
        return;
    }

    function markStage(step, ok) {
        if (stagesWrap) {
            stagesWrap.style.display = '';
        }
        var el = document.getElementById({$this->jsonString($formId)} + '_stage_' + step);
        if (!el) {
            return;
        }
        el.style.fontWeight = ok ? '700' : '400';
        el.style.color = ok ? '#2fc6f6' : '';
    }

    startButton.addEventListener('click', function() {
        var dialog = null;
        if (BX.UI && BX.UI.StepProcessing && BX.UI.StepProcessing.Dialog) {
            dialog = new BX.UI.StepProcessing.Dialog({
                minWidth: 560,
                messages: {title: 'Импорт CSV', summary: 'Подготовка импорта...'},
                optionsFields: [],
                handlers: {start: function() { runStages(this); }}
            });
            dialog.show();
            dialog.start();
        } else {
            runStages(null);
        }
    });

    function runStages(dialog) {
        markStage('parse', false);
        markStage('validate', false);
        markStage('import', false);

        executeStep('parse', dialog, form)
            .then(function(token) {
                markStage('parse', true);
                return executeStep('validate', dialog, form, token);
            })
            .then(function(token) {
                markStage('validate', true);
                return executeStep('import', dialog, form, token);
            })
            .then(function(summary) {
                markStage('import', true);
                if (dialog) {
                    dialog.setProgressBar(100, 100, 'Импорт');
                    dialog.setSummary(summary || 'Импорт завершен.');
                    dialog.showButton('close', true);
                    dialog.lockButton('start', true);
                }
                if (window.BX && BX.Main && BX.Main.gridManager) {
                    var grid = BX.Main.gridManager.getInstanceById({$this->jsonString($this->resource->getGridId())});
                    if (grid) { grid.reload(); }
                }
            })
            .catch(function(error) {
                if (dialog) {
                    dialog.setErrors([String(error || 'Ошибка импорта')]);
                    dialog.showButton('close', true);
                    dialog.lockButton('start', true);
                } else if (window.BX && BX.UI && BX.UI.Notification) {
                    BX.UI.Notification.Center.notify({content: String(error || 'Ошибка импорта')});
                }
            });
    }

    function executeStep(step, dialog, form, token) {
        var formData = new FormData();
        if (step === 'parse') {
            var fileInput = form.querySelector('input[name="import_file"]');
            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                return Promise.reject('Выберите CSV файл.');
            }
            formData.append('import_file', fileInput.files[0]);
        }

        formData.append('sessid', {$sessidJs});
        formData.append('action', 'import');
        formData.append('adminkit_import_step', step);
        formData.append('import_mode', (form.querySelector('[name="import_mode"]') || {}).value || 'create');
        formData.append('import_key_field', (form.querySelector('[name="import_key_field"]') || {}).value || 'ID');
        if (token) {
            formData.append('import_token', token);
        }

        return fetch({$actionUrlJs}, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).then(function(response) {
            return response.json();
        }).then(function(payload) {
            if (!payload || payload.success !== true) {
                throw (payload && payload.message) ? payload.message : 'Ошибка импорта.';
            }
            if (dialog && typeof payload.progress === 'number') {
                dialog.setProgressBar(100, payload.progress, 'Импорт');
            }
            if (dialog && payload.summary) {
                dialog.setSummary(payload.summary);
            }
            return payload.token || token || payload.summary || '';
        });
    }
});
</script>
HTML;
    }

    protected function handleImportStep(): void
    {
        if (!check_bitrix_sessid()) {
            $this->sendJson([
                'success' => false,
                'message' => 'Сессия истекла. Обновите страницу и повторите импорт.',
            ]);
            return;
        }

        $step = (string)$this->request->getPost('adminkit_import_step');
        $mode = $this->resolveImportMode((string)$this->request->getPost('import_mode'));
        $keyField = (string)($this->request->getPost('import_key_field') ?: 'ID');
        $baseContext = $this->buildImportContext($mode, $keyField);
        $action = ImportAction::make();
        $importer = new CsvImporter();

        if ($step === 'parse') {
            $file = $_FILES['import_file'] ?? null;
            $parse = $action->parse($file, $baseContext);
            if (!$parse->isSuccess()) {
                $this->sendJson(['success' => false, 'message' => $this->renderImportErrors($parse)]);
                return;
            }

            $importer->parseUploadedFile($file, $baseContext);
            $parsedRows = $importer->parsedRows();
            $mapping = $this->buildImportMapping($parsedRows);
            $mappedContext = $importer->mapRows($parsedRows, $mapping, $baseContext);
            $token = $this->createImportToken();
            $this->storeImportPayload($token, $mode, $keyField, $mappedContext->rows);
            $this->sendJson([
                'success' => true,
                'token' => $token,
                'progress' => 34,
                'summary' => sprintf('Файл загружен. Строк: %d.', count($mappedContext->rows)),
            ]);
            return;
        }

        $token = (string)$this->request->getPost('import_token');
        $stored = $this->loadImportPayload($token);
        if ($stored === null) {
            $this->sendJson(['success' => false, 'message' => 'Сессия импорта не найдена. Загрузите файл снова.']);
            return;
        }

        $context = $this->buildImportContext($stored['mode'], $stored['keyField'], $stored['rows']);
        if ($step === 'validate') {
            $validation = $action->validate($context);
            if (!$validation->isSuccess()) {
                $this->clearImportPayload($token);
                $this->sendJson(['success' => false, 'message' => $this->renderImportErrors($validation)]);
                return;
            }

            $this->sendJson([
                'success' => true,
                'token' => $token,
                'progress' => 67,
                'summary' => 'Проверка данных пройдена.',
            ]);
            return;
        }

        if ($step === 'import') {
            $result = $action->import($context);
            $this->clearImportPayload($token);
            if (!$result->isSuccess()) {
                $this->sendJson(['success' => false, 'message' => $this->renderImportErrors($result)]);
                return;
            }

            $this->sendJson([
                'success' => true,
                'progress' => 100,
                'summary' => sprintf(
                    'Импорт завершен: total=%d, created=%d, updated=%d, skipped=%d.',
                    $result->total,
                    $result->created,
                    $result->updated,
                    $result->skipped
                ),
            ]);
            return;
        }

        $this->sendJson(['success' => false, 'message' => 'Неизвестный этап импорта.']);
    }
    protected function resolveImportMode(string $mode): string
    {
        return in_array($mode, ['create', 'update', 'upsert'], true) ? $mode : 'create';
    }

    /** @param array<int,array<string,mixed>> $rows */
    protected function buildImportContext(string $mode, string $keyField, array $rows = []): ImportContext
    {
        return new ImportContext(
            resource: $this->resource,
            userId: $this->currentUserId(),
            mode: $mode,
            rows: $rows,
            request: $this->request,
            keyField: $keyField,
            maxRows: method_exists($this->resource, 'maxImportRows') ? $this->resource->maxImportRows() : 1000,
        );
    }

    protected function createImportToken(): string
    {
        return AdminString::id('adminkit_import', uniqid((string)$this->currentUserId(), true));
    }

    /** @param array<int,array<string,mixed>> $rows */
    protected function storeImportPayload(string $token, string $mode, string $keyField, array $rows): void
    {
        $_SESSION['MB_ADMIN_KIT_IMPORT'][$token] = [
            'mode' => $mode,
            'keyField' => $keyField,
            'rows' => $rows,
        ];
    }

    /** @return array{mode:string,keyField:string,rows:array<int,array<string,mixed>>}|null */
    protected function loadImportPayload(string $token): ?array
    {
        $payload = $_SESSION['MB_ADMIN_KIT_IMPORT'][$token] ?? null;
        if (!is_array($payload)) {
            return null;
        }

        return [
            'mode' => (string)($payload['mode'] ?? 'create'),
            'keyField' => (string)($payload['keyField'] ?? 'ID'),
            'rows' => is_array($payload['rows'] ?? null) ? $payload['rows'] : [],
        ];
    }

    protected function clearImportPayload(string $token): void
    {
        unset($_SESSION['MB_ADMIN_KIT_IMPORT'][$token]);
    }

    protected function jsonString(string $value): string
    {
        return (string)json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected function renderImportErrors(ImportResult $result): string
    {
        if ($result->errors === []) {
            return 'Р СњР ВµР С‘Р В·Р Р†Р ВµРЎРѓРЎвЂљР Р…Р В°РЎРЏ Р С•РЎв‚¬Р С‘Р В±Р С”Р В° Р С‘Р СР С—Р С•РЎР‚РЎвЂљР В°.';
        }

        $parts = [];
        foreach ($result->errors as $row => $messages) {
            foreach ((array)$messages as $message) {
                $parts[] = (string)$row . ': ' . (string)$message;
            }
        }

        return implode(' ', $parts);
    }

    /** @return array<string,mixed> */
    protected function readFilterValues(string $filterId): array
    {
        if (!class_exists(\Bitrix\Main\UI\Filter\Options::class)) {
            return [];
        }

        return (new \Bitrix\Main\UI\Filter\Options($filterId))->getFilter() ?: [];
    }

    protected function currentUserId(): mixed
    {
        global $USER;
        return is_object($USER) && method_exists($USER, 'GetID') ? $USER->GetID() : null;
    }

    protected function isSidePanelMode(): bool
    {
        return (string)$this->request->get('IFRAME') === 'Y';
    }

    protected function openImportInSidePanel(): void
    {
        $url = (new UrlGenerator($this->baseListUrl()))->actionUrl('import');
        $js = (new SidePanelAdapter($this->resource))->openJs($url, $this->resource->getGridId());
        $fallback = htmlspecialcharsbx($url);

        echo <<<HTML
<script>
BX.ready(function() {
    {$js}
});
</script>
<a class="ui-btn ui-btn-primary" href="{$fallback}">Р С›РЎвЂљР С”РЎР‚РЎвЂ№РЎвЂљРЎРЉ Р С‘Р СР С—Р С•РЎР‚РЎвЂљ</a>
HTML;
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
