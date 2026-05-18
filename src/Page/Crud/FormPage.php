<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Crud;

use Bitrix\Main\Localization\Loc;
use MB\Bitrix\AdminKit\Bitrix\Toolbar\ToolbarRenderer;
use MB\Bitrix\AdminKit\Component\ComponentContext;
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Renderers\VisibilityWrapper;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\Page\FormPageContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourcePersistenceContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\UI\FieldContainerContract;
use MB\Bitrix\AdminKit\Contracts\UI\ItemAwareContract;
use MB\Bitrix\AdminKit\Contracts\UI\PageTypeAwareContract;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Exceptions\AdminKitException;
use MB\Bitrix\AdminKit\Exceptions\PermissionDeniedException;
use MB\Bitrix\AdminKit\Field\FieldRenderContext;
use MB\Bitrix\AdminKit\Field\Renderers\FieldRowContext;
use MB\Bitrix\AdminKit\Field\Renderers\FieldRowRenderer;
use MB\Bitrix\AdminKit\Form\DataPipeline;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Manager\AssetManager;
use MB\Bitrix\AdminKit\Page\CrudPage;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\AdminKitJs;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Bitrix\AdminKit\Support\ResponseTerminator;
use Throwable;

class FormPage extends CrudPage implements FormPageContract
{
    protected ?DataWrapper $item = null;
    /** @var array<int,string> */
    protected array $globalErrors = [];
    protected bool $hasValidationErrors = false;
    /** @var array<string,mixed> */
    protected array $submittedValues = [];

    /** @var array<string,string[]> */
    protected array $fieldErrors = [];

    /** Set to true after a successful save inside a SidePanel (skips full-page redirect). */
    protected bool $savedInSidePanel = false;

    protected bool $showSavedNotice = false;

    protected string $formId = '';

    protected string $mode = 'create';
    public function __construct(?ResourceContract $resource = null, mixed $id = null, array $params = [])
    {
        parent::__construct($resource, $id, $params);
        $this->pageType = PageType::FORM;
        $this->id = $id;
        $this->mode = (string)($params['mode'] ?? ($this->id !== null ? 'edit' : 'create'));
    }

    public static function pageName(): string
    {
        return 'form';
    }

    public function render(): void
    {
        global $APPLICATION;
        Loc::loadMessages(__FILE__);
        if (Loc::getMessage('MB_ADMIN_KIT_FORM_SAVED') === null) {
            Loc::loadMessages(dirname(__DIR__) . '/FormPage.php');
        }

        $inPanel = $this->isSidePanelMode();

        $assetManager = (new AssetManager())->forForm();
        $assetManager->addExtensions(['ui', 'ui.layout-form', 'ui.buttons', 'ui.hint', 'ui.toolbar', 'ui.alerts', 'ui.notification']);

        if ($inPanel) {
            $assetManager->addExtensions('sidepanel');
        }

        $assetManager->load();

        $title = $this->id
            ? $this->resource->getTitle() . ' - ' . Loc::getMessage('MB_ADMIN_KIT_FORM_TITLE_EDIT', ['#ID#' => (string)$this->id])
            : $this->resource->getTitle() . ' - ' . Loc::getMessage('MB_ADMIN_KIT_FORM_TITLE_CREATE');
        $APPLICATION->SetTitle($title);

        $this->formId = 'adminkit-form-' . md5(static::class . ($this->id ?? ''));

        (new ToolbarRenderer())->renderForm($this->resource, $this->formId, $this->cancelActionJs());

        if ($this->id !== null && $this->id !== '') {
            if (!$this->resource instanceof ResourcePersistenceContract) {
                $this->globalErrors[] = 'Resource does not support persistence.';
            } else {
                $row = $this->resource->findItem($this->id);
                if ($row === null) {
                    $this->globalErrors[] = (string)Loc::getMessage('MB_ADMIN_KIT_FORM_ERR_NOT_FOUND');
                } else {
                    $this->item = DataWrapper::fromArray($row, $this->resource->getPrimaryKey());
                    if (!$this->resource->canView(new PermissionContext(resource: $this->resource, operation: 'view', item: $row))) {
                        $this->globalErrors[] = (string)Loc::getMessage('MB_ADMIN_KIT_FORM_ERR_CANNOT_VIEW');
                    }
                    if (!$this->resource->canUpdate(new PermissionContext(resource: $this->resource, operation: 'update', item: $row))) {
                        $this->globalErrors[] = (string)Loc::getMessage('MB_ADMIN_KIT_FORM_ERR_CANNOT_EDIT');
                    }
                }
            }
        } elseif (!$this->resource->canCreate(new PermissionContext(resource: $this->resource, operation: 'create'))) {
            $this->globalErrors[] = (string)Loc::getMessage('MB_ADMIN_KIT_FORM_ERR_CANNOT_CREATE');
        }

        if ($this->isPost() && !check_bitrix_sessid()) {
            $this->globalErrors[] = (string)Loc::getMessage('MB_ADMIN_KIT_FORM_SESSION_EXPIRED');
            if ($this->isAsyncSaveRequest()) {
                $this->sendAsyncSaveResponse();
                return;
            }
        } elseif ($this->isPost() && check_bitrix_sessid()) {
            if ($this->request->getPost('adminkit_action') === 'reactive') {
                $this->handleReactivePost();
                return;
            }
            if (!$this->isEditNotFound()) {
                $this->handlePost();
            }

            if ($this->isAsyncSaveRequest()) {
                $this->sendAsyncSaveResponse();
                return;
            }
        }

        if ($this->savedInSidePanel && $this->closeSidePanelAfterSave()) {
            echo '<script>window.top.BX.SidePanel.Instance.getTopSlider().close();</script>';

            return;
        }

        $this->renderAlerts();
        if (!$this->isEditNotFound()) {
            $this->renderForm();
            $this->renderDependencyScript($this->formId);
            $this->renderConditionalVisibilityScript($this->formId);
            if ($this->isAsync()) {
                $this->renderAsyncSaveScript();
            }
        }
        $this->renderHintInit();
    }

    public function isAsync(): bool
    {
        return $this->isAsync;
    }

    protected function renderHintInit(): void
    {
        echo <<<'HTML'
        <script>
        BX.ready(function() {
            if (BX.UI && BX.UI.Hint) {
                BX.UI.Hint.init(document.body);
            }
        });
        </script>
        HTML;
    }

    /** True when the page is rendered inside a Bitrix SidePanel. */
    protected function isSidePanelMode(): bool
    {
        return $this->request->get('IFRAME') === 'Y';
    }

    protected function isEditNotFound(): bool
    {
        return $this->id !== null && $this->id !== '' && $this->item === null;
    }

    protected function handlePost(): void
    {
        if ($this->isEditNotFound()) {
            return;
        }

        $fields = $this->collectAllFields();
        $raw = [];

        foreach ($fields as $field) {
            $column = $field->getColumn();
            $raw[$column] = $this->request->getPost($column);
        }
        $this->submittedValues = $raw;

        $formData = (new DataPipeline())->process($fields, $raw);
        $this->syncFormErrors($formData);

        $context = new DbOperationContext(
            resource: $this->resource,
            operation: $this->id ? 'update' : 'create',
            itemId: $this->id,
            oldData: $this->item?->toArray() ?? [],
            newData: $formData->validated(),
            rawData: $formData->raw(),
            normalizedData: $formData->normalized(),
            validatedData: $formData->validated(),
            request: $this->request,
        );

        try {
            $this->assertSavePermission($context);
            $this->resource->beforeValidate($formData, $context);
            $this->syncFormErrors($formData);
            $this->beforeSave($formData, $context);
            $this->syncFormErrors($formData);

            if ($formData->hasErrors() || $this->hasValidationErrors) {
                return;
            }

            $this->resource->afterValidate($formData, $context);

            if ($this->id) {
                if (!$this->resource instanceof ResourcePersistenceContract) {
                    throw new AdminKitException('Resource does not support persistence.');
                }
                $result = $this->resource->updateItemResult($this->id, $formData, $context);
                $savedId = $result->isSuccess() ? $this->id : null;
            } else {
                if (!$this->resource instanceof ResourcePersistenceContract) {
                    throw new AdminKitException('Resource does not support persistence.');
                }
                $result = $this->resource->createItemResult($formData, $context);
                $savedId = $result->isSuccess() ? $result->id() : null;
            }

            if ($savedId !== null) {
                $this->afterSave($formData, $context, $savedId);
            }
        } catch (AdminKitException $exception) {
            $this->globalErrors[] = $exception->getMessage();
            return;
        } catch (Throwable $exception) {
            $message = trim($exception->getMessage());
            if ($message === '') {
                $message = (string)Loc::getMessage('MB_ADMIN_KIT_FORM_ERR_SAVE_FAILED');
            }
            $this->globalErrors[] = $message;
            return;
        }

        if (!$result->isSuccess()) {
            foreach ($result->errors() as $error) {
                $this->globalErrors[] = $error;
            }
            return;
        }

        if ($savedId) {
            if ($this->isSidePanelMode()) {
                $this->savedInSidePanel = true;
                if (!$this->closeSidePanelAfterSave()) {
                    $this->showSavedNotice = true;
                    $this->reloadItemAfterSave($savedId);
                }
            } else {
                $redirectUrl = $this->redirectAfterSave($savedId);
                if ($redirectUrl === null) {
                    $backUrl = $this->request->getPost('back_url') ?: $this->request->getRequestUri();
                    $sep = str_contains($backUrl, '?') ? '&' : '?';
                    $redirectUrl = $backUrl . $sep . 'saved=1';
                }
                $this->redirect($redirectUrl);
            }
        }
    }


    /** @return iterable<FieldContract|ComponentContract> */
    public function fields(): iterable
    {
        return $this->resource->formFields();
    }

    /** @return iterable<Tab> */
    protected function tabs(): iterable
    {
        return $this->resource->formTabs();
    }

    protected function syncFormErrors(FormData $formData): void
    {
        foreach ($formData->errors() as $column => $messages) {
            foreach ($messages as $message) {
                $existing = $this->fieldErrors[$column] ?? [];
                if (!in_array($message, $existing, true)) {
                    $this->fieldErrors[$column][] = $message;
                }
                $this->hasValidationErrors = true;
            }
        }
    }

    protected function beforeSave(FormData $data, DbOperationContext $context): void
    {
    }

    protected function afterSave(FormData $data, DbOperationContext $context, mixed $savedId): void
    {
    }

    protected function redirectAfterSave(mixed $savedId): ?string
    {
        return null;
    }

    protected function assertSavePermission(DbOperationContext $context): void
    {
        $permission = new PermissionContext(
            resource: $this->resource,
            operation: $context->operation,
            item: $context->oldData ?: null,
        );

        if ($this->id && !$this->resource->canUpdate($permission)) {
            throw new PermissionDeniedException((string)Loc::getMessage('MB_ADMIN_KIT_FORM_ERR_CANNOT_EDIT'));
        }

        if (!$this->id && !$this->resource->canCreate($permission)) {
            throw new PermissionDeniedException((string)Loc::getMessage('MB_ADMIN_KIT_FORM_ERR_CANNOT_CREATE'));
        }
    }

    protected function renderAlerts(): void
    {
        if ($this->hasValidationErrors) {
            echo '<div class="ui-alert ui-alert-danger adminkit-alert">';
            echo '<span class="ui-alert-message">' . htmlspecialcharsbx((string)Loc::getMessage('MB_ADMIN_KIT_FORM_VALIDATION_ERROR')) . '</span>';
            echo '</div>';
        }

        if (!empty($this->globalErrors)) {
            echo '<div class="ui-alert ui-alert-danger adminkit-alert">';
            foreach ($this->globalErrors as $error) {
                echo '<span class="ui-alert-message">' . htmlspecialcharsbx((string)$error) . '</span><br>';
            }
            echo '</div>';
        }

        if ($this->request->get('saved') === '1' || $this->showSavedNotice) {
            echo '<div class="ui-alert ui-alert-success adminkit-alert"><span class="ui-alert-message">' . htmlspecialcharsbx((string)Loc::getMessage('MB_ADMIN_KIT_FORM_SAVED')) . '</span></div>';
        }
    }
    protected function renderForm(): void
    {
        $action = $this->request->getRequestUri();
        $tabs = iterator_to_array($this->tabs());
        $fid = htmlspecialcharsbx($this->formId);

        echo '<form id="' . $fid . '" method="POST" action="' . htmlspecialcharsbx($action) . '">';
        echo bitrix_sessid_post();

        if (!empty($tabs)) {
            $this->renderTabbedForm($tabs);
        } else {
            $this->renderFlatForm();
        }

        $this->renderButtons();
        echo '</form>';
    }

    protected function renderFlatForm(): void
    {
        $items = $this->getVisibleItems();
        $this->applyInitialDependencies($items);

        echo '<div class="ui-form ui-form-section">';

        foreach ($items as $item) {
            if ($item instanceof ComponentContract) {
                if ($item instanceof PageTypeAwareContract) {
                    $item = $item->withPageType(PageType::FORM);
                }
                if ($item instanceof ItemAwareContract) {
                    $item = $item->withItem($this->item);
                }
                $inner = $item->render();
                echo (new VisibilityWrapper())->wrap($inner, $item, new ComponentContext($this->item, PageType::FORM));
            } elseif ($item instanceof FieldContract) {
                $value = $this->resolveFieldValue($item->getColumn(), $this->item?->get($item->getColumn()));
                $this->renderFormRow($item, $value);
            }
        }
        echo '</div>';
    }

    /** @param array<int,Tab> $tabs */
    protected function renderTabbedForm(array $tabs): void
    {
        $hasActive = false;
        foreach ($tabs as $tab) {
            if ($tab->isActive()) {
                $hasActive = true;
                break;
            }
        }
        if (!$hasActive && !empty($tabs)) {
            $tabs[0]->active();
        }

        // Collect all tab items and apply dependencies before rendering
        $allTabItems = [];
        foreach ($tabs as $tab) {
            foreach ($tab->getItems() as $item) {
                $allTabItems[] = $item;
            }
        }
        $this->applyInitialDependencies($allTabItems);

        echo '<div class="ui-tabs" id="adminkit-form-tabs">';
        echo '<div class="ui-tabs-nav">';
        foreach ($tabs as $tab) {
            $activeClass = $tab->isActive() ? ' ui-tabs-nav-item-active' : '';
            $tabId = htmlspecialcharsbx('tab_' . $tab->getId());
            $title = htmlspecialcharsbx($tab->getTitle());
            echo '<a class="ui-tabs-nav-item' . $activeClass . '" href="#' . $tabId . '">' . $title . '</a>';
        }
        echo '</div>';

        foreach ($tabs as $tab) {
            $activeClass = $tab->isActive() ? ' ui-tabs-panel-active' : '';
            $tabId = htmlspecialcharsbx('tab_' . $tab->getId());

            echo '<div id="' . $tabId . '" class="ui-tabs-panel' . $activeClass . '">';
            echo '<div class="ui-form">';

            foreach ($tab->getItems() as $item) {
                if ($item instanceof ComponentContract) {
                    if ($item instanceof PageTypeAwareContract) {
                        $item = $item->withPageType(PageType::FORM);
                    }
                    if ($item instanceof ItemAwareContract) {
                        $item = $item->withItem($this->item);
                    }
                    $inner = $item->render();
                    echo (new VisibilityWrapper())->wrap($inner, $item, new ComponentContext($this->item, PageType::FORM));
                } elseif ($item instanceof FieldContract && $item->isVisibleOn(PageType::FORM)) {
                    $value = $this->resolveFieldValue($item->getColumn(), $this->item?->get($item->getColumn()));
                    $this->renderFormRow($item, $value);
                }
            }

            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
        echo '<script>BX.ready(function(){ if(MB.UI && MB.UI.Tabs) { new MB.UI.Tabs({node: BX("adminkit-form-tabs")}); } });</script>';
    }

    protected function renderFormRow(FieldContract $field, mixed $value): void
    {
        echo (new FieldRowRenderer())->render(new FieldRowContext(
            field: $field,
            value: $value,
            item: $this->item,
            pageType: $this->pageType,
            renderContext: new FieldRenderContext(
                field: $field,
                resource: $this->resource,
                item: $this->item,
                value: $value,
                page: 'form',
                row: $this->item?->toArray() ?? [],
                errors: $this->fieldErrors[$field->getColumn()] ?? [],
                meta: ['mode' => $this->mode],
            ),
            errors: $this->fieldErrors[$field->getColumn()] ?? [],
        ));
    }

    protected function renderButtons(): void
    {
        $cancelAction = $this->cancelActionJs();

        echo '<div class="ui-button-panel adminkit-button-panel">';
        echo '<button type="submit" class="ui-btn ui-btn-success" id="'.$this->formId.'-submit" name="save" value="Y">' . htmlspecialcharsbx((string)Loc::getMessage('MB_ADMIN_KIT_FORM_SAVE')) . '</button>';
        echo '<button type="button" class="ui-btn ui-btn-link" onclick="' . htmlspecialcharsbx(
            $cancelAction
        ) . '">' . htmlspecialcharsbx((string)Loc::getMessage('MB_ADMIN_KIT_FORM_CANCEL')) . '</button>';
        echo '</div>';
    }

    protected function cancelActionJs(): string
    {
        return $this->isSidePanelMode()
            ? 'window.top.BX.SidePanel.Instance.getTopSlider().close()'
            : 'window.history.back()';
    }

    /** @return array<int, FieldContract|ComponentContract> */
    protected function getVisibleItems(): array
    {
        $items = [];
        foreach ($this->fields() as $item) {
            if ($item instanceof ComponentContract) {
                $items[] = $item;
            } elseif ($item instanceof FieldContract && $item->isVisibleOn(PageType::FORM)) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /** @return FieldContract[] */
    protected function getVisibleFields(): array
    {
        $fields = [];
        foreach ($this->getVisibleItems() as $item) {
            if ($item instanceof FieldContainerContract) {
                $fields = array_merge($fields, $item->extractFields());
            } elseif ($item instanceof FieldContract) {
                $fields[] = $item;
            }
        }
        return $fields;
    }

    /**
     * Apply dependsOn() modifiers to all fields in $items using the current item's saved values.
     * Mutates field instances in-place so the subsequent render loop sees correct state.
     *
     * @param array<int, FieldContract|ComponentContract> $items
     */
    protected function applyInitialDependencies(array $items): void
    {
        $allFields = [];
        foreach ($items as $item) {
            if ($item instanceof FieldContainerContract) {
                $allFields = array_merge($allFields, $item->extractFields());
            } elseif ($item instanceof FieldContract) {
                $allFields[] = $item;
            }
        }

        $formData = [];
        foreach ($allFields as $field) {
            $column = $field->getColumn();
            $formData[$column] = $this->resolveFieldValue($column, $this->item?->get($column));
        }

        foreach ($allFields as $field) {
            if (method_exists($field, 'hasDependency') && $field->hasDependency()) {
                $field->applyDependency($formData);
            }
        }
    }


    /**
     * AJAX handler for dependsOn() field dependencies.
     * Applies dependency modifiers and returns re-rendered field HTML as JSON.
     */
    protected function handleReactivePost(): void
    {
        $fields = $this->collectAllFields();
        $formData = [];

        foreach ($fields as $field) {
            $formData[$field->getColumn()] = $field->serializePostValue($this->request->getPost($field->getColumn()));
        }

        if ($this->id && $this->item === null && $this->resource instanceof ResourcePersistenceContract) {
            $row = $this->resource->findItem($this->id);
            $this->item = $row ? DataWrapper::fromArray($row, $this->resource->getPrimaryKey()) : null;
        }

        $result = [];
        foreach ($fields as $field) {
            if (!method_exists($field, 'hasDependency') || !$field->hasDependency()) {
                continue;
            }
            $field->applyDependency($formData);
            $value = $formData[$field->getColumn()] ?? $this->item?->get($field->getColumn());
            $result[$field->getColumn()] = ['html' => $field->renderFormField($value)];
        }

        ResponseTerminator::clearOutputBuffers();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'fields' => $result]);
        ResponseTerminator::terminate();
    }

    /**
     * Render JS that watches source columns, shows disabled/loading states on dependent
     * fields, and re-renders them via AJAX when a source value changes.
     */
    protected function renderDependencyScript(string $formId): void
    {
        $sourceCols = [];
        $dependsMap = [];

        foreach ($this->collectAllFields() as $field) {
            if (method_exists($field, 'hasDependency') && $field->hasDependency()) {
                $dependsMap[$field->getColumn()] = $field->getDependsOn();
                foreach ($field->getDependsOn() as $col) {
                    $sourceCols[$col] = true;
                }
            }
        }

        if ($sourceCols === []) {
            return;
        }

        AdminKitJs::renderInit('Dependencies', [
            'formId' => $formId,
            'sourceCols' => array_keys($sourceCols),
            'dependsMap' => $dependsMap,
        ]);
    }

    protected function renderConditionalVisibilityScript(string $formId): void
    {
        AdminKitJs::renderInit('Visibility', [
            'formId' => $formId,
        ]);
    }

    /** @return FieldContract[] all writable fields from both flat form and tabs */
    protected function collectAllFields(): array
    {
        $tabs = iterator_to_array($this->tabs());
        if (!empty($tabs)) {
            $fields = [];
            foreach ($tabs as $tab) {
                foreach ($tab->getItems() as $item) {
                    if ($item instanceof FieldContainerContract) {
                        $fields = array_merge($fields, $item->extractFields());
                    } elseif ($item instanceof FieldContract && $item->isVisibleOn(PageType::FORM)) {
                        $fields[] = $item;
                    }
                }
            }
        } else {
            $fields = $this->getVisibleFields();
        }

        return array_values(array_filter($fields, fn (FieldContract $f) => !$f->isReadOnly()));
    }
    protected function resolveFieldValue(string $column, mixed $fallback): mixed
    {
        return array_key_exists($column, $this->submittedValues) ? $this->submittedValues[$column] : $fallback;
    }

    protected function closeSidePanelAfterSave(): bool
    {
        return $this->resource->closeSidePanelAfterSave();
    }

    protected function reloadItemAfterSave(mixed $savedId): void
    {
        if ($this->id === null || $this->id === '') {
            $this->id = $savedId;
            $this->mode = 'edit';
        }

        if ($this->resource instanceof ResourcePersistenceContract) {
            $row = $this->resource->findItem($this->id);
            if ($row !== null) {
                $this->item = DataWrapper::fromArray($row, $this->resource->getPrimaryKey());
            }
        }
    }

    protected function isAsyncSaveRequest(): bool
    {
        if ((string)$this->request->getPost('adminkit_async_save') === 'Y') {
            return true;
        }

        return $this->isSidePanelMode() && $this->isAjaxRequest();
    }

    protected function isAjaxRequest(): bool
    {
        return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    protected function sendAsyncSaveResponse(): void
    {
        ResponseTerminator::clearOutputBuffers();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => !$this->hasValidationErrors && $this->globalErrors === [],
            'validationError' => $this->hasValidationErrors,
            'globalErrors' => $this->globalErrors,
            'fieldErrors' => $this->fieldErrors,
            'closeSidePanel' => $this->savedInSidePanel && $this->closeSidePanelAfterSave(),
            'reloadParentGrid' => $this->savedInSidePanel && !$this->closeSidePanelAfterSave(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ResponseTerminator::terminate();
    }

    protected function renderAsyncSaveScript(): void
    {
        AdminKitJs::renderInit('Form', [
            'formId' => $this->formId,
            'gridId' => $this->resource->getGridId(),
            'messages' => [
                'validationError' => (string)Loc::getMessage('MB_ADMIN_KIT_FORM_VALIDATION_ERROR'),
                'saved' => (string)Loc::getMessage('MB_ADMIN_KIT_FORM_SAVED'),
            ],
        ]);
    }
}
