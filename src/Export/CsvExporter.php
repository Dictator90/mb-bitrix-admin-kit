<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Export;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Bitrix\AdminKit\Support\AdminString;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

final class CsvExporter implements ExporterInterface
{
    public function __construct(
        private readonly string $delimiter = ',',
        private readonly string $enclosure = '"',
        private readonly string $escape = '\\',
        private readonly bool $withBom = true,
    ) {
    }

    public function supports(string $format): bool
    {
        return strtolower($format) === 'csv';
    }

    public function export(iterable $rows, ExportContext $context): ExportResult
    {
        $fields = $this->exportableFields($context);
        if ($fields === []) {
            return ExportResult::failure('No exportable fields configured.');
        }

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return ExportResult::failure('Unable to create CSV buffer.');
        }

        if ($this->withBom) {
            fwrite($handle, "\xEF\xBB\xBF");
        }

        fputcsv(
            $handle,
            array_map(static fn (FieldContract $field): string => $field->getLabel(), $fields),
            $this->delimiter,
            $this->enclosure,
            $this->escape,
        );

        foreach (AdminCollection::make($rows)->all() as $row) {
            $row = is_array($row) ? $row : (array)$row;
            $line = [];
            foreach ($fields as $field) {
                $value = method_exists($field, 'isComputed') && $field->isComputed()
                    ? $field->computeValue($row)
                    : ($row[$field->getColumn()] ?? null);

                if (method_exists($field, 'displayValue')) {
                    $value = $field->displayValue(
                        $value,
                        $row,
                        ['page' => 'export', 'field' => $field, 'context' => $context],
                    );
                }

                $line[] = $this->stringify($value);
            }
            fputcsv($handle, $line, $this->delimiter, $this->enclosure, $this->escape);
        }

        rewind($handle);
        $content = (string)stream_get_contents($handle);
        fclose($handle);

        return ExportResult::success(
            $content,
            AdminString::safeKey($context->resource::getId() . '-' . date('Y-m-d-His')) . '.csv',
        );
    }

    /** @return array<int,FieldContract> */
    private function exportableFields(ExportContext $context): array
    {
        $fields = $context->fields !== [] ? $context->fields : AdminCollection::make($context->resource->indexFields())->all();

        return array_values(array_filter($fields, function (mixed $field): bool {
            if (!$field instanceof FieldContract) {
                return false;
            }

            if (!$field->isVisibleOn(PageType::INDEX)) {
                return false;
            }

            if (method_exists($field, 'isExportable') && !$field->isExportable()) {
                return false;
            }

            if (method_exists($field, 'isPrivate') && $field->isPrivate()) {
                return false;
            }

            if (method_exists($field, 'isSystem') && $field->isSystem()) {
                return false;
            }

            return true;
        }));
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value) || $value instanceof \Stringable) {
            return (string)$value;
        }

        return (string)json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
