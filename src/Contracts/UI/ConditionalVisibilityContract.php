<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\UI;

interface ConditionalVisibilityContract
{
    public function visibleWhen(string $column, mixed $value): static;

    public function getVisibleWhen(): ?array;
}
