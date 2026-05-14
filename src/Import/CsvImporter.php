<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Import;

use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Form\DataPipeline;
use MB\Bitrix\AdminKit\Form\FormData;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

final class CsvImporter implements ImporterInterface
{
    /** @var array<int,array<string,mixed>> */
    private array $lastParsedRows = [];

    public function __construct(
        private readonly string $delimiter = ',',
        private readonly string $enclosure = '"',
        private readonly string $escape = '\\',
    ) {}

    public function parseUploadedFile(mixed $file, ImportContext $context): ImportResult
    {
        $path = $this->resolvePath($file);
        if ($path === null || !is_readable($path)) {
            return (new ImportResult())->addError('file', 'Uploaded CSV file is not readable.');
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return (new ImportResult())->addError('file', 'Unable to open uploaded CSV file.');
        }

        $headers = null;
        $rows = [];
        $line = 0;
        while (($data = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            $line++;
            if ($line === 1) {
                if ($data !== []) {
                    $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$data[0]);
                }
                $headers = array_map(static fn(mixed $value): string => trim((string)$value), $data);
                continue;
            }

            $maxRows = method_exists($context->resource, 'maxImportRows')
                ? min($context->maxRows, $context->resource->maxImportRows())
                : $context->maxRows;
            if (count($rows) >= $maxRows) {
                fclose($handle);
                $this->lastParsedRows = $rows;
                return (new ImportResult(count($rows), errors: ['limit' => ['Import row limit exceeded.']]));
            }

            if ($this->isEmptyCsvRow($data)) {
                continue;
            }

            $row = [];
            foreach ($headers ?? [] as $index => $header) {
                $row[$header] = $data[$index] ?? null;
            }
            $rows[] = $row;
        }
        fclose($handle);

        $this->lastParsedRows = $rows;

        return new ImportResult(total: count($rows));
    }

    /** @return array<int,array<string,mixed>> */
    public function parsedRows(): array
    {
        return $this->lastParsedRows;
    }

    public function mapRows(iterable $rows, array $mapping, ImportContext $context): ImportContext
    {
        $mapped = [];
        foreach (AdminCollection::make($rows)->all() as $row) {
            $row = is_array($row) ? $row : (array)$row;
            $item = [];
            foreach (AdminCollection::make($mapping)->all() as $source => $target) {
                if ($target === null || $target === '') {
                    continue;
                }
                $item[(string)$target] = $row[(string)$source] ?? null;
            }
            $mapped[] = $item;
        }

        return $context->withRows($rows, $mapped);
    }

    public function validateRows(ImportContext $context): ImportResult
    {
        $rows = $context->mappedRows !== [] ? $context->mappedRows : $context->rawRows;
        $result = (new ImportResult())->withTotal(count($rows));

        foreach ($rows as $index => $row) {
            $formData = $this->pipeline($context, $row);
            if ($formData->hasErrors()) {
                $result = $result->addError($index + 1, $this->flattenErrors($formData->errors()))->withSkipped();
            }
        }

        return $result;
    }

    public function importRows(ImportContext $context): ImportResult
    {
        $rows = $context->mappedRows !== [] ? $context->mappedRows : $context->rawRows;
        $result = $this->validateRows($context);
        if (!$result->isSuccess() || $context->validateOnly) {
            return $result;
        }

        $result = (new ImportResult())->withTotal(count($rows));
        foreach ($rows as $index => $row) {
            $formData = $this->pipeline($context, $row);
            if ($formData->hasErrors()) {
                $result = $result->addError($index + 1, $this->flattenErrors($formData->errors()))->withSkipped();
                continue;
            }

            $operation = $this->resolveOperation($context, $formData);
            if ($operation === null) {
                $result = $result->addError($index + 1, 'Import mode requires a non-empty key field value.')->withSkipped();
                continue;
            }

            if (!$this->canPersist($context, $operation, $formData)) {
                $result = $result->addError($index + 1, ucfirst($operation) . ' permission denied.')->withSkipped();
                continue;
            }

            if ($operation === 'create') {
                $dbResult = $context->resource->createItemResult($formData);
                if ($dbResult->isSuccess()) {
                    $result = $result->withCreated();
                } else {
                    $result = $result->addError($index + 1, $dbResult->errors())->withSkipped();
                }
                continue;
            }

            $id = $formData->validated()[$context->keyField] ?? null;
            $dbResult = $context->resource->updateItemResult($id, $formData);
            if ($dbResult->isSuccess()) {
                $result = $result->withUpdated();
            } else {
                $result = $result->addError($index + 1, $dbResult->errors())->withSkipped();
            }
        }

        return $result;
    }

    /** @return array<int,FieldContract> */
    private function fields(ImportContext $context): array
    {
        return array_values(array_filter(
            AdminCollection::make($context->resource->formFields())->all(),
            static function (mixed $field): bool {
                if (!$field instanceof FieldContract) {
                    return false;
                }

                if (!$field->isVisibleOn(PageType::FORM)) {
                    return false;
                }

                if (method_exists($field, 'isImportable') && !$field->isImportable()) {
                    return false;
                }

                if (method_exists($field, 'isPrivate') && $field->isPrivate()) {
                    return false;
                }

                if (method_exists($field, 'isSystem') && $field->isSystem()) {
                    return false;
                }

                return true;
            },
        ));
    }

    private function pipeline(ImportContext $context, array $row): FormData
    {
        return (new DataPipeline())->process($this->fields($context), $row);
    }

    private function resolveOperation(ImportContext $context, FormData $formData): ?string
    {
        if ($context->mode === 'create') {
            return 'create';
        }

        $key = $formData->validated()[$context->keyField] ?? null;
        if ($key === null || $key === '') {
            return null;
        }

        if ($context->mode === 'update') {
            return 'update';
        }

        if ($context->mode === 'upsert') {
            return $context->resource->findItem($key) ? 'update' : 'create';
        }

        return null;
    }

    private function canPersist(ImportContext $context, string $operation, FormData $formData): bool
    {
        $permissionContext = new PermissionContext(
            $context->userId,
            null,
            $context->resource,
            $operation,
            $operation === 'update' ? $context->resource->findItem($formData->validated()[$context->keyField] ?? null) : null,
        );

        return $operation === 'create'
            ? $context->resource->canCreate($permissionContext)
            : $context->resource->canUpdate($permissionContext);
    }

    /** @return string[] */
    private function flattenErrors(array $errors): array
    {
        $flat = [];
        foreach ($errors as $messages) {
            foreach ((array)$messages as $message) {
                $flat[] = (string)$message;
            }
        }

        return $flat;
    }

    private function resolvePath(mixed $file): ?string
    {
        if (is_string($file)) {
            return $file;
        }

        if (is_array($file)) {
            return isset($file['tmp_name']) ? (string)$file['tmp_name'] : null;
        }

        if ($file instanceof \SplFileInfo) {
            return $file->getPathname();
        }

        return null;
    }

    /** @param array<int,mixed> $row */
    private function isEmptyCsvRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string)$value) !== '') {
                return false;
            }
        }

        return true;
    }
}
