<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Import;

use Closure;
use MB\Bitrix\AdminKit\Security\PermissionContext;

final class ImportAction
{
    private Closure|bool $canRunCondition = true;

    public function __construct(
        private readonly string $id = 'import',
        private readonly string $label = 'Импорт',
        private readonly ImporterInterface $importer = new CsvImporter(),
    ) {}

    public static function make(string $id = 'import', string $label = 'Импорт'): self
    {
        return new self($id, $label);
    }

    public function canRun(Closure|bool $condition): self
    {
        $this->canRunCondition = $condition;

        return $this;
    }

    public function parse(mixed $file, ImportContext $context): ImportResult
    {
        if (!$this->isRunnable($context)) {
            return (new ImportResult())->addError('action', 'Import action is not allowed.');
        }

        return $this->importer->parseUploadedFile($file, $context);
    }

    public function preview(mixed $file, array $mapping, ImportContext $context): ImportResult
    {
        $result = $this->parse($file, $context);
        if (!$result->isSuccess()) {
            return $result;
        }

        $rows = method_exists($this->importer, 'parsedRows') ? $this->importer->parsedRows() : $context->rawRows;
        $mapped = $this->importer->mapRows($rows, $mapping, $context);

        return $this->importer->validateRows($mapped);
    }

    public function validateOnly(ImportContext $context): ImportResult
    {
        if (!$this->isRunnable($context)) {
            return (new ImportResult())->addError('action', 'Import action is not allowed.');
        }

        return $this->importer->validateRows($context);
    }

    public function import(ImportContext $context): ImportResult
    {
        if (!$this->isRunnable($context)) {
            return (new ImportResult())->addError('action', 'Import action is not allowed.');
        }

        if (!$this->hasBasePermission($context)) {
            return (new ImportResult())->addError('permission', 'Import permission denied.');
        }

        return $this->importer->importRows($context);
    }

    private function isRunnable(ImportContext $context): bool
    {
        if (is_bool($this->canRunCondition)) {
            return $this->canRunCondition;
        }

        return (bool)($this->canRunCondition)($context);
    }

    private function hasBasePermission(ImportContext $context): bool
    {
        $resource = $context->resource;
        if ($context->mode === 'create') {
            return $resource->canCreate(new PermissionContext($context->userId, null, $resource, 'import.create'));
        }

        if ($context->mode === 'update') {
            return $resource->canUpdate(new PermissionContext($context->userId, null, $resource, 'import.update'));
        }

        return $resource->canCreate(new PermissionContext($context->userId, null, $resource, 'import.create'))
            || $resource->canUpdate(new PermissionContext($context->userId, null, $resource, 'import.update'));
    }
}
