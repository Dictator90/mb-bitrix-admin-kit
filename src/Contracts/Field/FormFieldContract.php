<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

interface FormFieldContract
{
    public function renderFormField(mixed $value = null): string;
}
