<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Export;

interface ExporterInterface
{
    public function supports(string $format): bool;

    public function export(iterable $rows, ExportContext $context): ExportResult;
}
