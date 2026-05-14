<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Export;

use Closure;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class ExportAction
{
    /** @var ExporterInterface[] */
    private array $exporters;
    private Closure|bool $canRunCondition = true;
    private bool $allowRunByFilter = true;
    private bool $allowRunAll = false;

    public function __construct(
        private readonly string $id = 'export',
        private readonly string $label = 'Экспорт',
        ?ExporterInterface $exporter = null,
    ) {
        $this->exporters = [$exporter ?? new CsvExporter()];
    }

    public static function make(string $id = 'export', string $label = 'Экспорт'): self
    {
        return new self($id, $label);
    }

    public function exporter(ExporterInterface $exporter): self
    {
        $this->exporters[] = $exporter;

        return $this;
    }

    public function canRun(Closure|bool $condition): self
    {
        $this->canRunCondition = $condition;

        return $this;
    }

    public function allowRunByFilter(bool $allow = true): self
    {
        $this->allowRunByFilter = $allow;

        return $this;
    }

    public function allowRunAll(bool $allow = true): self
    {
        $this->allowRunAll = $allow;

        return $this;
    }

    public function execute(ExportContext $context): ExportResult
    {
        if (!$context->resource->canView(new PermissionContext($context->userId, null, $context->resource, 'export'))) {
            return ExportResult::failure('Export permission denied.');
        }

        if (!$this->isRunnable($context)) {
            return ExportResult::failure('Export action is not allowed.');
        }

        if (
            !$context->hasSelectedIds()
            && !$context->hasFilter()
            && !$this->allowRunAll
            && !(method_exists($context->resource, 'allowExportAll') && $context->resource->allowExportAll())
        ) {
            return ExportResult::failure('Exporting all records is disabled by default. Select records or pass an explicit filter.');
        }

        if (
            !$context->hasSelectedIds()
            && $context->hasFilter()
            && (
                !$this->allowRunByFilter
                || (method_exists($context->resource, 'allowExportByFilter') && !$context->resource->allowExportByFilter())
            )
        ) {
            return ExportResult::failure('Export by filter is disabled for this action.');
        }

        $exporter = $this->resolveExporter($context->format);
        if ($exporter === null) {
            return ExportResult::failure('Unsupported export format.');
        }

        return $exporter->export($this->rows($context), $context);
    }

    private function isRunnable(ExportContext $context): bool
    {
        if (is_bool($this->canRunCondition)) {
            return $this->canRunCondition;
        }

        return (bool)($this->canRunCondition)($context);
    }

    private function resolveExporter(string $format): ?ExporterInterface
    {
        foreach ($this->exporters as $exporter) {
            if ($exporter->supports($format)) {
                return $exporter;
            }
        }

        return null;
    }

    /** @return array<int,array<string,mixed>> */
    private function rows(ExportContext $context): array
    {
        $resource = $context->resource;
        $gridContext = $context->gridContext;
        $params = [];

        if ($gridContext !== null) {
            $params = (new GridQueryBuilder())->build($resource, $gridContext);
            unset($params['limit'], $params['offset']);
        }

        if ($context->hasSelectedIds()) {
            $params['filter'] = array_replace($params['filter'] ?? [], [
                $resource->getPrimaryKey() => AdminCollection::make($context->selectedIds)->all(),
            ]);
        } elseif ($context->hasFilter()) {
            $params['filter'] = array_replace($params['filter'] ?? [], $context->filter);
        }

        $rows = $resource->getList($params);
        if ($gridContext !== null) {
            $rows = $resource->afterIndexRows($rows, $gridContext);
            $rows = array_map(static fn(array $row): array => $resource->mapIndexRow($row, $gridContext), $rows);
        }

        return $rows;
    }
}
