<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

interface ComponentContract
{
    public function render(): string;

    public function withItem(?DataWrapper $item): static;

    public function withPageType(PageType $type): static;

    /** @return FieldContract[] */
    public function extractFields(): array;

    public function __toString(): string;
}
