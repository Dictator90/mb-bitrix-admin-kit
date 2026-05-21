<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Context;

final readonly class AdminKitContext
{
    public function __construct(
        public string $scopeId,
        public string $moduleId,
        public bool $insideModule,
        public bool $adminSection,
        public ?string $basePath = null,
    ) {
    }
}
