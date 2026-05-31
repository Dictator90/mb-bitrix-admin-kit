<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Crud\Handlers;

use Bitrix\Main\Localization\Loc;
use MB\Bitrix\AdminKit\Contracts\Resource\DataManagerResourceContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourcePersistenceContract;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Exceptions\AdminKitException;
use MB\Bitrix\AdminKit\Form\DataPipeline;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Relation\EntityObjectFormSaver;
use MB\Bitrix\AdminKit\Support\ComponentPostHandlers;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\ExceptionDiagnostics;
use MB\Bitrix\AdminKit\Support\ResponseTerminator;
use Throwable;

final class FormPagePostHandler
{
    public function handle(FormPage $page): void
    {
        if ($page->getIsEditNotFound()) {
            return;
        }

        if ($page->getResource() instanceof DataManagerResourceContract) {
            $this->handleDataManagerObjectPost($page);
            return;
        }

        $this->handleCrudResourcePost($page);
    }

    public function handleCrudResourcePost(FormPage $page): void
    {
        $fields = $page->collectAllFields();
        $raw = [];

        foreach ($fields as $field) {
            $column = $field->getColumn();
            $raw[$column] = $page->request->getPost($column);
        }
        $page->setSubmittedValues($raw);

        $formData = (new DataPipeline())->process($fields, $raw);
        $this->syncFormErrors($page, $formData);

        $resource = $page->getResource();

        $context = new DbOperationContext(
            resource: $resource,
            operation: $page->getId() ? 'update' : 'create',
            itemId: $page->getId(),
            oldData: $page->getItemValue()?->toArray() ?? [],
            newData: $formData->validated(),
            rawData: $formData->raw(),
            normalizedData: $formData->normalized(),
            validatedData: $formData->validated(),
            request: $page->request,
            adminKitContext: $page->adminKitContext(),
            eventModuleId: $page->adminKitContext()?->moduleId ?? 'main',
        );

        try {
            $page->triggerAssertSavePermission($context);
            if ($resource instanceof \MB\Bitrix\AdminKit\Contracts\Resource\ResourceLifecycleContract) {
                $resource->beforeValidate($formData, $context);
            }
            $this->syncFormErrors($page, $formData);
            $page->triggerBeforeSave($formData, $context);
            $this->syncFormErrors($page, $formData);

            if ($formData->hasErrors() || $page->hasValidationErrors()) {
                return;
            }

            if ($resource instanceof \MB\Bitrix\AdminKit\Contracts\Resource\ResourceLifecycleContract) {
                $resource->afterValidate($formData, $context);
            }

            $resource = $page->getResource();
            if ($page->getId()) {
                if (!$resource instanceof ResourcePersistenceContract) {
                    throw new AdminKitException('Resource does not support persistence.');
                }
                $result = $resource->updateItemResult($page->getId(), $formData, $context);
                $savedId = $result->isSuccess() ? $page->getId() : null;
            } else {
                if (!$resource instanceof ResourcePersistenceContract) {
                    throw new AdminKitException('Resource does not support persistence.');
                }
                $result = $resource->createItemResult($formData, $context);
                $savedId = $result->isSuccess() ? $result->id() : null;
            }

            if ($savedId !== null) {
                $page->triggerAfterSave($formData, $context, $savedId);
            }
        } catch (AdminKitException $exception) {
            $page->addGlobalError($exception->getMessage());
            return;
        } catch (Throwable $exception) {
            $fallback = (string)Loc::getMessage('MB_ADMIN_KIT_FORM_ERR_SAVE_FAILED');
            foreach (ExceptionDiagnostics::toGlobalErrors($exception, $fallback) as $error) {
                $page->addGlobalError($error);
            }
            return;
        }

        if (!$result->isSuccess()) {
            foreach ($result->errors() as $error) {
                $page->addGlobalError($error);
            }
            return;
        }

        if ($savedId) {
            $this->finishSuccessfulSave($page, $savedId);
        }
    }

    public function handleDataManagerObjectPost(FormPage $page): void
    {
        $resource = $page->getResource();
        if (!$resource instanceof DataManagerResourceContract) {
            return;
        }

        $fields = $page->collectAllFields();
        $raw = [];
        foreach ($fields as $field) {
            $column = $field->getColumn();
            $raw[$column] = $page->request->getPost($column);
        }
        $page->setSubmittedValues($raw);

        $context = new DbOperationContext(
            resource: $resource,
            operation: $page->getId() ? 'update' : 'create',
            itemId: $page->getId(),
            oldData: $page->getItemValue()?->toArray() ?? [],
            newData: [],
            rawData: $raw,
            normalizedData: [],
            validatedData: [],
            request: $page->request,
            adminKitContext: $page->adminKitContext(),
            eventModuleId: $page->adminKitContext()?->moduleId ?? 'main',
        );

        try {
            $page->triggerAssertSavePermission($context);

            $saver = new EntityObjectFormSaver();
            [$scalarFields] = $saver->splitFields($fields);
            $rawScalar = [];
            foreach ($scalarFields as $field) {
                $column = $field->getColumn();
                $rawScalar[$column] = $field->serializePostValue($raw[$column] ?? null);
            }
            $rawScalar['_mode'] = $page->getFormMode();
            $rawScalar['_id'] = $page->getId() ?? '';

            $formData = (new DataPipeline())->process($scalarFields, $rawScalar);
            $this->syncFormErrors($page, $formData);

            $resource->beforeValidate($formData, $context);
            $this->syncFormErrors($page, $formData);
            $page->triggerBeforeSave($formData, $context);
            $this->syncFormErrors($page, $formData);

            if ($formData->hasErrors() || $page->hasValidationErrors()) {
                return;
            }

            $resource->afterValidate($formData, $context);

            $result = $saver->save($resource, $page->getId(), $fields, $raw, $context, $formData->validated());
            if (!$result->success) {
                foreach ($result->globalErrors as $error) {
                    $page->addGlobalError($error);
                }
                foreach ($result->fieldErrors as $column => $messages) {
                    foreach ($messages as $message) {
                        $page->addFieldError($column, $message);
                    }
                }
                return;
            }

            $savedId = $result->savedId;
            if ($savedId !== null) {
                $page->triggerAfterSave($formData, $context, $savedId);
            }

            $this->finishSuccessfulSave($page, $savedId);
        } catch (AdminKitException $exception) {
            $page->addGlobalError($exception->getMessage());
        } catch (Throwable $exception) {
            $fallback = (string)Loc::getMessage('MB_ADMIN_KIT_FORM_ERR_SAVE_FAILED');
            foreach (ExceptionDiagnostics::toGlobalErrors($exception, $fallback) as $error) {
                $page->addGlobalError($error);
            }
        }
    }

    public function handleReactivePost(FormPage $page): void
    {
        $fields = $page->collectAllFields();
        $formData = [];

        foreach ($fields as $field) {
            $formData[$field->getColumn()] = $field->serializePostValue($page->request->getPost($field->getColumn()));
        }
        foreach ($_POST as $key => $rawValue) {
            if (!is_string($key) || $key === '' || array_key_exists($key, $formData)) {
                continue;
            }

            $formData[$key] = $rawValue;
        }

        $resource = $page->getResource();
        if ($page->getId() && $page->getItemValue() === null && $resource instanceof ResourcePersistenceContract) {
            $row = $resource->findItem($page->getId());
            $primaryKey = 'ID';
            if ($resource instanceof \MB\Bitrix\AdminKit\Contracts\Resource\ResourceOrmContract) {
                $primaryKey = $resource->getPrimaryKey();
            }
            $page->setItemValue($row ? DataWrapper::fromArray($row, $primaryKey) : null);
        }

        $result = [];
        foreach ($fields as $field) {
            if (!method_exists($field, 'hasDependency') || !$field->hasDependency()) {
                continue;
            }
            if (method_exists($field, 'applyDependency')) {
                /** @var mixed $field */
                $field->applyDependency($formData);
            }
            $value = $formData[$field->getColumn()] ?? $page->getItemValue()?->get($field->getColumn());
            $result[$field->getColumn()] = ['html' => $field->renderFormField($value, $formData)];
        }

        ResponseTerminator::clearOutputBuffers();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'fields' => $result]);
        ResponseTerminator::terminate();
    }

    public function finishSuccessfulSave(FormPage $page, mixed $savedId): void
    {
        if ($savedId === null || $savedId === '') {
            return;
        }

        if ($page->getIsAsyncSaveRequest()) {
            if (!$page->tryReloadItemAfterSave($savedId)) {
                return;
            }

            if ($page->getIsSidePanelMode()) {
                $page->setSavedInSidePanel(true);
                if (!$page->closeSidePanelAfterSave()) {
                    $page->setShowSavedNotice(true);
                }
                return;
            }

            $page->setShowSavedNotice(true);
            return;
        }

        if ($page->getIsSidePanelMode()) {
            $page->setSavedInSidePanel(true);
            if (!$page->closeSidePanelAfterSave()) {
                if (!$page->tryReloadItemAfterSave($savedId)) {
                    return;
                }
                $page->setShowSavedNotice(true);
            }
            return;
        }

        $redirectUrl = $page->triggerRedirectAfterSave($savedId);
        if ($redirectUrl === null) {
            $backUrl = $page->request->getPost('back_url') ?: $page->request->getRequestUri();
            $sep = str_contains($backUrl, '?') ? '&' : '?';
            $redirectUrl = $backUrl . $sep . 'saved=1';
        }

        $page->redirect($redirectUrl);
    }

    protected function syncFormErrors(FormPage $page, FormData $formData): void
    {
        foreach ($formData->errors() as $column => $messages) {
            if (!is_array($messages)) {
                continue;
            }
            foreach ($messages as $message) {
                $page->addFieldError($column, $message);
            }
        }
    }
}
