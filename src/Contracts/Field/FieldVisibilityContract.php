<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

use Closure;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Support\Conditionable\ConditionTree;

interface FieldVisibilityContract
{
    public function isVisibleOn(PageType $pageType): bool;

    public function visibleWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null): static;

    public function getVisibleWhen(): ?array;
}
