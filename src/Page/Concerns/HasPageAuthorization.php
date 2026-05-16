<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Concerns;

use MB\Bitrix\AdminKit\Contracts\Resource\ResourceAuthorizationContract;
use MB\Bitrix\AdminKit\Security\PermissionContext;

trait HasPageAuthorization
{
    public function canView(?PermissionContext $context = null): bool
    {
        if (!$this->hasResource()) {
            return true;
        }

        $resource = $this->resource();
        if (!$resource instanceof ResourceAuthorizationContract) {
            return true;
        }

        return $resource->canView($context);
    }
}
