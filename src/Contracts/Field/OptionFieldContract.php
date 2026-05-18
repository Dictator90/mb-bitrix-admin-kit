<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

interface OptionFieldContract
{
    /** @return array<mixed> */
    public function getOptions(array $context = []): array;
}
