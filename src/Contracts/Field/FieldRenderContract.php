<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

interface FieldRenderContract
{
    public function renderIndex(mixed $context, array $row = []): string;

    /** @param array<string,mixed> $data */
    public function renderForm(mixed $context = null, array $data = []): string;

    public function renderDetail(mixed $context, array $row = []): string;

    public function previewValue(mixed $value): mixed;
}
