<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Export;

final class ExportResult
{
    /** @param string[] $errors */
    public function __construct(
        public readonly string $content = '',
        public readonly string $filename = '',
        public readonly string $contentType = 'text/csv; charset=UTF-8',
        public readonly array $errors = [],
    ) {
    }

    public static function success(string $content, string $filename, string $contentType = 'text/csv; charset=UTF-8'): self
    {
        return new self($content, $filename, $contentType);
    }

    /** @param string|string[] $errors */
    public static function failure(string|array $errors): self
    {
        return new self(errors: array_values((array)$errors));
    }

    public function isSuccess(): bool
    {
        return $this->errors === [];
    }
}
