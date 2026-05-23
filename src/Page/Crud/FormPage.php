<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Crud;

use Bitrix\Main\Localization\Loc;
use MB\Bitrix\AdminKit\Bitrix\Toolbar\ToolbarRenderer;
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\Page\FormPageContract;
use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourcePersistenceContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\UI\FieldContainerContract;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Exceptions\PermissionDeniedException;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Manager\AssetManager;
use MB\Bitrix\AdminKit\Page\Crud\Handlers\FormPageFormRenderer;
use MB\Bitrix\AdminKit\Page\Crud\Handlers\FormPagePostHandler;
use MB\Bitrix\AdminKit\Page\CrudPage;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Bitrix\AdminKit\Support\ExceptionDiagnostics;
use MB\Bitrix\AdminKit\Support\ResponseTerminator;
use Throwable;

class FormPage extends CrudPage implements FormPageContract
{
    protected ?DataWrapper $item = null;
    protected ?object $entityItem = null;
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

        (new ToolbarRenderer())->renderForm($this->resource, $this->formId, $this->getCancelActionJs());

        if ($this->id !== null && $this->id !== '') {
            if (!$this->resource instanceof ResourcePersistenceContract) {
                $this->globalErrors[] = 'Resource does not support persistence.';
            } else {
                if ($this->resource instanceof DataManagerResourceContract) {
                    $select = $this->resource->relationSelectForFields($this->resource->formFields());
                    $this->entityItem = $this->resource->findObject($this->id, $select);
                    $row = null;
                    if ($this->entityItem !== null && method_exists($this->entityItem, 'collectValues')) {
                        $row = $this->entityItem->collectValues();
                    }
                } else {
                    $row = $this->resource->findItem($this->id);
                }

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

        $this->renderFormPage();
    }

    public function getId(): mixed
    {
        return $this->id;
    }

    /** @internal */
    public function setId(mixed $id): void
    {
        $this->id = $id;
    }

    public function getFormMode(): string
    {
        return $this->mode;
    }

    /** @internal */
    public function setFormMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function getItemValue(): ?DataWrapper
    {
        return $this->item;
    }

    /** @internal */
    public function setItemValue(?DataWrapper $item): void
    {
        $this->item = $item;
    }

    public function getEntityItemInstance(): ?object
    {
        return $this->entityItem;
    }

    /** @internal */
    public function setEntityItemInstance(?object $entityItem): void
    {
        $this->entityItem = $entityItem;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSubmittedValues(): array
    {
        return $this->submittedValues;
    }

    /**
     * @param array<string, mixed> $values
     * @internal
     */
    public function setSubmittedValues(array $values): void
    {
        $this->submittedValues = $values;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getFieldErrors(): array
    {
        return $this->fieldErrors;
    }

    /**
     * @param array<string, array<int, string>> $errors
     * @internal
     */
    public function setFieldErrors(array $errors): void
    {
        $this->fieldErrors = $errors;
    }

    /** @internal */
    public function addFieldError(string $column, string $message): void
    {
        $existing = $this->fieldErrors[$column] ?? [];
        if (!in_array($message, $existing, true)) {
            $this->fieldErrors[$column][] = $message;
        }
        $this->hasValidationErrors = true;
    }

    /**
     * @return array<int, string>
     */
    public function getGlobalErrors(): array
    {
        return $this->globalErrors;
    }

    /**
     * @param array<int, string> $errors
     * @internal
     */
    public function setGlobalErrors(array $errors): void
    {
        $this->globalErrors = $errors;
    }

    /** @internal */
    public function addGlobalError(string $error): void
    {
        $this->globalErrors[] = $error;
    }

    public function hasValidationErrors(): bool
    {
        return $this->hasValidationErrors;
    }

    /** @internal */
    public function setHasValidationErrors(bool $flag): void
    {
        $this->hasValidationErrors = $flag;
    }

    public function getFormId(): string
    {
        return $this->formId;
    }

    public function getSavedInSidePanel(): bool
    {
        return $this->savedInSidePanel;
    }

    /** @internal */
    public function setSavedInSidePanel(bool $flag): void
    {
        $this->savedInSidePanel = $flag;
    }

    public function getShowSavedNotice(): bool
    {
        return $this->showSavedNotice;
    }

    /** @internal */
    public function setShowSavedNotice(bool $flag): void
    {
        $this->showSavedNotice = $flag;
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

    /**
     * @return iterable<Tab>
     * @internal
     */
    public function getTabsList(): iterable
    {
        return $this->tabs();
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

    /** @internal */
    public function triggerBeforeSave(FormData $data, DbOperationContext $context): void
    {
        $this->beforeSave($data, $context);
    }

    /** @internal */
    public function triggerAfterSave(FormData $data, DbOperationContext $context, mixed $savedId): void
    {
        $this->afterSave($data, $context, $savedId);
    }

    /** @internal */
    public function triggerRedirectAfterSave(mixed $savedId): ?string
    {
        return $this->redirectAfterSave($savedId);
    }

    /** @internal */
    public function triggerAssertSavePermission(DbOperationContext $context): void
    {
        $this->assertSavePermission($context);
    }

    /** True when the page is rendered inside a Bitrix SidePanel. */
    protected function isSidePanelMode(): bool
    {
        return $this->request->get('IFRAME') === 'Y';
    }

    /** @internal */
    public function getIsSidePanelMode(): bool
    {
        return $this->isSidePanelMode();
    }

    protected function isEditNotFound(): bool
    {
        return $this->id !== null && $this->id !== '' && $this->item === null;
    }

    /** @internal */
    public function getIsEditNotFound(): bool
    {
        return $this->isEditNotFound();
    }

    /**
     * @return array<int, FieldContract> all writable fields from both flat form and tabs
     * @internal
     */
    public function collectAllFields(): array
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

        $formData = $this->formConditionContext();

        return array_values(array_filter(
            $fields,
            static fn (FieldContract $field): bool => !$field->isReadOnlyFor($formData),
        ));
    }

    /** @return array<string,mixed> */
    protected function formConditionContext(): array
    {
        $context = $this->item?->toArray() ?? [];
        $context['_mode'] = $this->mode;
        $context['_id'] = $this->id ?? '';

        if ($this->id !== null && $this->id !== '') {
            $resource = $this->resource;
            if ($resource instanceof \MB\Bitrix\AdminKit\Contracts\Resource\ResourceOrmContract) {
                $context[$resource->getPrimaryKey()] = $this->id;
            }
        }

        return array_merge($context, $this->submittedValues);
    }

    /**
     * @return array<string, mixed>
     * @internal
     */
    public function formConditionContextList(): array
    {
        return $this->formConditionContext();
    }

    /** @internal */
    public function resolveFieldValueForField(FieldContract $field): mixed
    {
        $column = $field->getColumn();

        if (array_key_exists($column, $this->submittedValues)) {
            return $this->submittedValues[$column];
        }

        $row = $this->formConditionContext();

        if (
            $field instanceof \MB\Bitrix\AdminKit\Field\Relation\RelationField
            && $this->resource instanceof DataManagerResourceContract
            && $this->entityItem !== null
        ) {
            $resolved = $this->resource->resolveRelationValue($this->entityItem, $field);
            if ($resolved !== null && $resolved !== '') {
                return $resolved;
            }
        }

        return $field->resolveValue($this->item, $row);
    }

    /** @internal */
    public function closeSidePanelAfterSave(): bool
    {
        return $this->resource->closeSidePanelAfterSave();
    }

    /** @internal */
    public function tryReloadItemAfterSave(mixed $savedId): bool
    {
        try {
            $this->reloadItemAfterSave($savedId);
        } catch (Throwable $exception) {
            $fallback = (string)Loc::getMessage('MB_ADMIN_KIT_FORM_ERR_SAVE_FAILED');
            array_push($this->globalErrors, ...ExceptionDiagnostics::toGlobalErrors($exception, $fallback));
            return false;
        }

        return true;
    }

    protected function reloadItemAfterSave(mixed $savedId): void
    {
        if ($this->id === null || $this->id === '') {
            $this->id = $savedId;
            $this->mode = 'edit';
        }

        if ($this->resource instanceof ResourcePersistenceContract) {
            if ($this->resource instanceof DataManagerResourceContract) {
                $select = $this->resource->relationSelectForFields($this->resource->formFields());
                $this->entityItem = $this->resource->findObject($this->id, $select);
                if ($this->entityItem !== null && method_exists($this->entityItem, 'collectValues')) {
                    $this->item = DataWrapper::fromArray($this->entityItem->collectValues(), $this->resource->getPrimaryKey());
                }
            } else {
                $row = $this->resource->findItem($this->id);
                if ($row !== null) {
                    $this->item = DataWrapper::fromArray($row, $this->resource->getPrimaryKey());
                }
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

    /** @internal */
    public function getIsAsyncSaveRequest(): bool
    {
        return $this->isAsyncSaveRequest();
    }

    protected function isAjaxRequest(): bool
    {
        return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    /** @internal */
    public function getIsAjaxRequest(): bool
    {
        return $this->isAjaxRequest();
    }

    /** @internal */
    public function sendAsyncSaveResponse(): void
    {
        ResponseTerminator::clearOutputBuffers();

        header('Content-Type: application/json; charset=utf-8');
        $success = !$this->hasValidationErrors && $this->globalErrors === [];
        echo json_encode([
            'success' => $success,
            'validationError' => $this->hasValidationErrors,
            'globalErrors' => $this->globalErrors,
            'fieldErrors' => $this->fieldErrors,
            'gridId' => $this->resource->getGridId(),
            'closeSidePanel' => $success && $this->savedInSidePanel && $this->closeSidePanelAfterSave(),
            'reloadParentGrid' => $success && $this->savedInSidePanel,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ResponseTerminator::terminate();
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

    /**
     * @return array<int, FieldContract|ComponentContract>
     * @internal
     */
    public function getVisibleItemsList(): array
    {
        return $this->getVisibleItems();
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
            $formData[$column] = $this->resolveFieldValueForField($field);
        }

        foreach ($allFields as $field) {
            if (method_exists($field, 'hasDependency') && $field->hasDependency()) {
                $field->applyDependency($formData);
            }
        }
    }

    /**
     * @param array<int, FieldContract|ComponentContract> $items
     * @internal
     */
    public function applyInitialDependenciesList(array $items): void
    {
        $this->applyInitialDependencies($items);
    }

    public function getCancelActionJs(): string
    {
        return $this->isSidePanelMode()
            ? 'window.top.BX.SidePanel.Instance.getTopSlider().close()'
            : 'window.history.back()';
    }

    // --- Protected Helper Methods Delegating to Handlers for Backward Compatibility ---

    protected function handlePost(): void
    {
        if ($this->isEditNotFound()) {
            return;
        }

        if ($this->resource instanceof DataManagerResourceContract) {
            $this->handleDataManagerObjectPost();
            return;
        }

        $this->handleCrudResourcePost();
    }

    protected function handleCrudResourcePost(): void
    {
        (new FormPagePostHandler())->handleCrudResourcePost($this);
    }

    protected function handleDataManagerObjectPost(): void
    {
        (new FormPagePostHandler())->handleDataManagerObjectPost($this);
    }

    protected function finishSuccessfulSave(mixed $savedId): void
    {
        (new FormPagePostHandler())->finishSuccessfulSave($this, $savedId);
    }

    protected function handleReactivePost(): void
    {
        (new FormPagePostHandler())->handleReactivePost($this);
    }

    protected function renderFormPage(): void
    {
        (new FormPageFormRenderer())->render($this);
    }

    protected function renderAlerts(): void
    {
        (new FormPageFormRenderer())->renderAlerts($this);
    }

    protected function renderForm(): void
    {
        (new FormPageFormRenderer())->renderForm($this);
    }

    protected function renderFlatForm(): void
    {
        (new FormPageFormRenderer())->renderFlatForm($this);
    }

    /**
     * @param array<int, \MB\Bitrix\AdminKit\Component\Layout\Tab> $tabs
     */
    protected function renderTabbedForm(array $tabs): void
    {
        (new FormPageFormRenderer())->renderTabbedForm($this, $tabs);
    }

    protected function renderFormRow(FieldContract $field, mixed $value): void
    {
        (new FormPageFormRenderer())->renderFormRow($this, $field, $value);
    }

    protected function renderButtons(): void
    {
        (new FormPageFormRenderer())->renderButtons($this);
    }

    protected function renderDependencyScript(string $formId): void
    {
        (new FormPageFormRenderer())->renderDependencyScript($this, $formId);
    }

    protected function renderConditionalVisibilityScript(string $formId): void
    {
        (new FormPageFormRenderer())->renderConditionalVisibilityScript($formId);
    }

    protected function renderAsyncSaveScript(): void
    {
        (new FormPageFormRenderer())->renderAsyncSaveScript($this);
    }

    protected function renderHintInit(): void
    {
        (new FormPageFormRenderer())->renderHintInit();
    }
}
