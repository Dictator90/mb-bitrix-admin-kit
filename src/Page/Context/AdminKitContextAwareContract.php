<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Context;

interface AdminKitContextAwareContract
{
    public function setAdminKitContext(AdminKitContext $context): void;
}
