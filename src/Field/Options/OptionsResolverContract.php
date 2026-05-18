<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Options;

use MB\Bitrix\AdminKit\Field\Select;

interface OptionsResolverContract
{
    /** @return array<mixed> */
    public function resolve(array $context, Select $field): array;
}
