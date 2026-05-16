<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Page;

use MB\Bitrix\AdminKit\Contracts\ResourceContract;

interface ResourcePageContract extends PageContract
{
    public function resource(): ResourceContract;

    public function getResource(): ResourceContract;

    public function setResource(ResourceContract $resource): static;

    public function hasResource(): bool;
}
