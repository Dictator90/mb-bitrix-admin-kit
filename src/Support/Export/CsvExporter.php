<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support\Export;

use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

class CsvExporter
{
    protected string $delimiter = ';';
    protected string $enclosure = '"';
    protected string $charset = 'windows-1251';

    public function __construct(protected ResourceContract $resource)
    {
    }

    public function delimiter(string $delimiter): static
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    public function enclosure(string $enclosure): static
    {
        $this->enclosure = $enclosure;

        return $this;
    }

    public function charset(string $charset): static
    {
        $this->charset = $charset;

        return $this;
    }

    public function export(array $ormFilter = []): never
    {
        $fields = $this->getExportFields();
        $filename = $this->buildFilename();

        // Disable output buffering
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=' . $this->charset);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        // BOM for Excel UTF-8 detection
        if (strtolower($this->charset) === 'utf-8') {
            fwrite($out, "\xEF\xBB\xBF");
        }

        // Header row
        $headers = array_map(fn(FieldContract $f) => $this->encode($f->getLabel()), $fields);
        fputcsv($out, $headers, $this->delimiter, $this->enclosure);

        // Data rows
        $dataManagerClass = $this->resource->getDataManagerClass();
        if ($dataManagerClass) {
            $select = array_map(fn(FieldContract $f) => $f->getColumn(), $fields);
            $result = $dataManagerClass::getList([
                'select' => $select,
                'filter' => $ormFilter,
                'order' => [$this->resource->getPrimaryKey() => 'DESC'],
            ]);

            while ($row = $result->fetch()) {
                $values = [];
                foreach ($fields as $field) {
                    $rawValue = $row[$field->getColumn()] ?? '';
                    $values[] = $this->encode((string)$field->previewValue($rawValue));
                }
                fputcsv($out, $values, $this->delimiter, $this->enclosure);
            }
        }

        fclose($out);
        exit;
    }

    /** @return FieldContract[] */
    protected function getExportFields(): array
    {
        $fields = [];
        foreach ($this->resource->indexFields() as $field) {
            if ($field->isVisibleOn(PageType::INDEX)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    protected function buildFilename(): string
    {
        $title = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->resource->getTitle());
        $date = date('Y-m-d_H-i');

        return ($title ?: 'export') . '_' . $date . '.csv';
    }

    protected function encode(string $value): string
    {
        if (strtolower($this->charset) !== 'utf-8') {
            return iconv('UTF-8', $this->charset . '//IGNORE', $value);
        }

        return $value;
    }
}
