<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Export;

use Closure;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;

final class ExportAction
{
    /** @var ExporterInterface[] */
    private array $exporters;
    private Closure|bool $canRunCondition = true;
    private bool $allowRunByFilter = true;
    private bool $allowRunAll = false;
    private string $id;
    private string $label;

    public function __construct(
        string $id = 'export',
        ?string $label = null,
        ?ExporterInterface $exporter = null,
    ) {
        $this->id = $id;
        $this->label = $label ?? LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_EXPORT_LABEL', 'Export');
        $this->exporters = [$exporter ?? new CsvExporter()];
    }

    public static function make(string $id = 'export', ?string $label = null): self
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
            return ExportResult::failure(LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_EXPORT_PERMISSION_DENIED', 'Export permission denied.'));
        }

        if (!$this->isRunnable($context)) {
            return ExportResult::failure(LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_EXPORT_ACTION_NOT_ALLOWED', 'Export action is not allowed.'));
        }

        if (
            !$context->hasSelectedIds()
            && !$context->hasFilter()
            && !$this->allowRunAll
            && !$context->resource->allowExportAll()
        ) {
            return ExportResult::failure(LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_EXPORT_ALL_DISABLED', 'Exporting all records is disabled by default. Select records or pass an explicit filter.'));
        }

        if (
            !$context->hasSelectedIds()
            && $context->hasFilter()
            && (
                !$this->allowRunByFilter
                || !$context->resource->allowExportByFilter()
            )
        ) {
            return ExportResult::failure(LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_EXPORT_FILTER_DISABLED', 'Export by filter is disabled for this action.'));
        }

        $maxRows = $this->maxExportRows($context);
        if ($maxRows <= 0) {
            return ExportResult::failure(LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_EXPORT_ACTION_NOT_ALLOWED', 'Export is disabled for this resource.'));
        }

        $rowCount = $this->countExportRows($context);
        if ($rowCount > $maxRows) {
            return ExportResult::failure($this->tooManyRowsMessage($maxRows));
        }

        $exporter = $this->resolveExporter($context->format);
        if ($exporter === null) {
            return ExportResult::failure(LocalizedMessage::get(__FILE__,'MB_ADMIN_KIT_EXPORT_UNSUPPORTED_FORMAT', 'Unsupported export format.'));
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

    private function maxExportRows(ExportContext $context): int
    {
        return $context->resource->maxExportRows();
    }

    private function countExportRows(ExportContext $context): int
    {
        if ($context->hasSelectedIds()) {
            return count($context->selectedIds);
        }

        $filter = $this->buildQueryParams($context)['filter'] ?? [];

        return $context->resource->getCount($filter);
    }

    private function tooManyRowsMessage(int $maxRows): string
    {
        return LocalizedMessage::get(
            __FILE__,
            'MB_ADMIN_KIT_EXPORT_TOO_MANY_ROWS',
            'Too many rows to export. Maximum: #MAX#.',
            ['#MAX#' => (string) $maxRows],
        );
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

    /**
     * @return array<string,mixed>
     */
    private function buildQueryParams(ExportContext $context): array
    {
        $resource = $context->resource;
        $params = [];

        if ($context->gridContext !== null) {
            $params = (new GridQueryBuilder())->build($resource, $context->gridContext);
            unset($params['limit'], $params['offset']);
        }

        if ($context->hasSelectedIds()) {
            $params['filter'] = array_replace($params['filter'] ?? [], [
                $resource->getPrimaryKey() => AdminCollection::make($context->selectedIds)->all(),
            ]);
        } elseif ($context->hasFilter()) {
            $params['filter'] = array_replace($params['filter'] ?? [], $context->filter);
        }

        return $params;
    }

    /** @return array<int,array<string,mixed>> */
    private function rows(ExportContext $context): array
    {
        $resource = $context->resource;
        $gridContext = $context->gridContext;
        $params = $this->buildQueryParams($context);

        $rows = $resource->getList($params);
        if ($gridContext !== null) {
            $rows = $resource->afterIndexRows($rows, $gridContext);
            $rows = array_map(static fn (array $row): array => $resource->mapIndexRow($row, $gridContext), $rows);
        }

        return $rows;
    }
}
