<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Import;

interface ImporterInterface
{
    public function parseUploadedFile(mixed $file, ImportContext $context): ImportResult;

    public function mapRows(iterable $rows, array $mapping, ImportContext $context): ImportContext;

    public function validateRows(ImportContext $context): ImportResult;

    public function importRows(ImportContext $context): ImportResult;
}
