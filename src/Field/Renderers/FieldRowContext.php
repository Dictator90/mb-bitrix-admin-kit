<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Renderers;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Field\FieldRenderContext;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

final class FieldRowContext
{
    /** @param list<string> $errors */
    public function __construct(
        public readonly FieldContract $field,
        public readonly mixed $value = null,
        public readonly ?DataWrapper $item = null,
        public readonly PageType $pageType = PageType::FORM,
        public readonly ?FieldRenderContext $renderContext = null,
        public readonly array $errors = [],
        public readonly mixed $sourceValueResolver = null,
    ) {
    }
}
