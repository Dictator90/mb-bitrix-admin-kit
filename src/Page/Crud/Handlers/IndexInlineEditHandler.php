<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Crud\Handlers;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourcePersistenceContract;
use MB\Bitrix\AdminKit\Database\DbOperationContext;
use MB\Bitrix\AdminKit\Form\DataPipeline;
use MB\Bitrix\AdminKit\Grid\Row\GridRowId;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Security\PermissionContext;

final class IndexInlineEditHandler
{
    public function handle(IndexPage $page): bool
    {
        if (!$page->hasResource()) {
            return false;
        }

        $resource = $page->resource();
        $actionKey = 'action_button_' . $resource->getGridId();
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

            $messages = array_merge($messages, $this->saveInlineRow($page, $id, $this->sanitizeInlinePayload($payload)));
        }

        if ($messages !== []) {
            $_SESSION['MB_ADMIN_KIT_BULK_RESULT'][$resource->getGridId()] = [
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
    public function saveInlineRow(IndexPage $page, mixed $id, array $payload): array
    {
        $resource = $page->resource();
        if (!$resource instanceof ResourcePersistenceContract) {
            return [];
        }
        if (GridRowId::isGroupId($id)) {
            return [];
        }
        $id = GridRowId::normalizeItemId($id);
        if ($id === null || $id === '') {
            return [];
        }
        $idLabel = (string)$id;
        $oldRow = $resource->findItem($id);
        if (!is_array($oldRow)) {
            return ["Row {$idLabel}: item was not found."];
        }

        $permission = new PermissionContext(resource: $resource, operation: 'update', item: $oldRow);
        if (!$resource->canUpdate($permission)) {
            return ["Row {$idLabel}: update permission denied."];
        }

        $merged = array_merge($oldRow, $payload);
        $fields = $this->resolveInlineFields($page, array_keys($payload));
        $formData = (new DataPipeline())->process($fields, $merged);

        $context = new DbOperationContext(
            resource: $resource,
            operation: 'update',
            itemId: $id,
            oldData: $oldRow,
            newData: $formData->validated(),
            rawData: $formData->raw(),
            normalizedData: $formData->normalized(),
            validatedData: $formData->validated(),
            request: $page->request,
        );

        $resource->beforeValidate($formData, $context);
        if ($formData->hasErrors()) {
            return $this->flattenInlineErrors($page, $id, $formData->errors());
        }
        $resource->afterValidate($formData, $context);

        $result = $resource->updateItemResult($id, $formData, $context);
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
    private function resolveInlineFields(IndexPage $page, array $editedColumns): array
    {
        $allowed = array_flip($editedColumns);
        $result = [];
        $known = [];

        foreach ($page->resource()->formFields() as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }
            $known[$field->getColumn()] = $field;
        }

        foreach ($page->fields() as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }
            $known[$field->getColumn()] ??= $field;
        }

        foreach ($known as $column => $field) {
            if (!isset($allowed[$column]) || $field->isReadOnly()) {
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
    private function sanitizeInlinePayload(array $payload): array
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
    private function flattenInlineErrors(IndexPage $page, mixed $id, array $errors): array
    {
        $idLabel = (string)$id;
        $messages = [];
        foreach ($errors as $column => $columnMessages) {
            foreach ($columnMessages as $message) {
                $template = $page->message('MB_ADMIN_KIT_INDEX_ROW_ERROR_TEMPLATE', 'Row #ID#, #COLUMN#: #MESSAGE#');
                $messages[] = str_replace(
                    ['#ID#', '#COLUMN#', '#MESSAGE#'],
                    [$idLabel, (string)$column, (string)$message],
                    $template,
                );
            }
        }

        return $messages;
    }
}
