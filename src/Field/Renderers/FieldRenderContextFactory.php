<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Renderers;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\Resource\CrudResourceContract;
use MB\Bitrix\AdminKit\Field\FieldRenderContext;

final class FieldRenderContextFactory
{
    /** @param array<string,mixed> $row */
    /** @param array<string,array<int,string>|string> $errors */
    /** @param array<string,mixed> $meta */
    public function make(
        FieldContract $field,
        CrudResourceContract $resource,
        mixed $item = null,
        mixed $value = null,
        string $page = 'form',
        array $row = [],
        array $errors = [],
        array $meta = [],
    ): FieldRenderContext {
        return new FieldRenderContext(
            field: $field,
            resource: $resource,
            item: $item,
            value: $value,
            page: $page,
            row: $row,
            errors: $errors,
            meta: $meta,
        );
    }
}
