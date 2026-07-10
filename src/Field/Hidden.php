<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Support\Enums\PageType;

class Hidden extends Field
{
    public function __construct(?string $label = null, ?string $column = null)
    {
        parent::__construct($label, $column);
        $this->sortable = false;
        $this->hideOn(PageType::INDEX, PageType::DETAIL);
    }

    public function renderFormField(mixed $value = null): string
    {
        $val = htmlspecialcharsbx((string)$this->resolveValue($value));
        $name = htmlspecialcharsbx($this->column);

        return '<input type="hidden" name="' . $name . '" value="' . $val . '">';
    }
}
