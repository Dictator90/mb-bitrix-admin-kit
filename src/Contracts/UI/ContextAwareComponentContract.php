<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\UI;

use MB\Bitrix\AdminKit\Component\ComponentContext;

interface ContextAwareComponentContract
{
    public function withContext(ComponentContext $context): static;
}
