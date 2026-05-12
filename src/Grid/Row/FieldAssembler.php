<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Row;

interface FieldAssembler
{
    /**
     * @param array{data: array<string, mixed>, columns: array<string, mixed>} $row
     * @return array{data: array<string, mixed>, columns: array<string, mixed>}
     */
    public function processRow(array $row): array;
}
