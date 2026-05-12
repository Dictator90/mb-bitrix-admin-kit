<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Support\Enums\PageType;

class ID extends Field
{
    public function __construct(string $label = 'ID', ?string $column = 'ID')
    {
        parent::__construct($label, $column);
        $this->hideOn(PageType::FORM);
    }

    public function renderFormField(mixed $value = null): string
    {
        $val = htmlspecialcharsbx((string)$this->resolveValue($value));

        return '<span>' . $val . '</span>';
    }
}
