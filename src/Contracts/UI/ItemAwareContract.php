<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\UI;

use MB\Bitrix\AdminKit\Support\DataWrapper;

interface ItemAwareContract
{
    public function withItem(?DataWrapper $item): static;
}
