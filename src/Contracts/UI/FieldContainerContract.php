<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\UI;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;

interface FieldContainerContract
{
    /** @return list<FieldContract> */
    public function extractFields(): array;
}
