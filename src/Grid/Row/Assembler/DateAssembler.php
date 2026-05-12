<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Row\Assembler;

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;

class DateAssembler implements FieldAssembler
{
    public function __construct(
        protected array $columnIds,
        protected string $format = 'd.m.Y',
    ) {}

    public function processRow(array $row): array
    {
        foreach ($this->columnIds as $id) {
            $raw = $row['data'][$id] ?? null;
            $row['columns'][$id] = $this->formatValue($raw);
        }

        return $row;
    }

    protected function formatValue(mixed $value): string
    {
        if ($value instanceof DateTime) {
            return $value->format($this->format . ' H:i:s');
        }

        if ($value instanceof Date) {
            return $value->format($this->format);
        }

        if (is_string($value) && $value !== '') {
            try {
                return (new \DateTime($value))->format($this->format);
            } catch (\Exception) {
            }
        }

        return (string)($value ?? '');
    }
}
