<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout;

final class TabsConfig
{
    public function __construct(
        public readonly string $containerId,
        public readonly bool $remember,
    ) {
    }
}
