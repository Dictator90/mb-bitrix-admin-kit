<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\UI;

interface AssetAwareContract
{
    /** @return list<string> */
    public function getRequiredExtensions(): array;
}
