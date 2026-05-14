<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Security;

use MB\Bitrix\AdminKit\Contracts\ResourceContract;

final class PermissionContext
{
    public function __construct(
        public readonly mixed $userId = null,
        public readonly ?string $moduleId = null,
        public readonly ?ResourceContract $resource = null,
        public readonly string $operation = '',
        public readonly array|object|null $item = null,
    ) {}
}
