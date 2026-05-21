<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\Resource\CrudResourceContract;

final class FieldRenderContext
{
    /**
     * @param array<string,mixed> $row
     * @param array<int,string> $errors
     * @param array<string,mixed> $meta
     */
    public function __construct(
        public readonly FieldContract $field,
        public readonly CrudResourceContract $resource,
        public readonly mixed $item = null,
        public readonly mixed $value = null,
        public readonly string $page = '',
        public readonly array $row = [],
        public readonly array $errors = [],
        public readonly array $meta = [],
    ) {
    }
}
