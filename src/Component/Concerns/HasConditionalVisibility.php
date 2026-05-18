<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Concerns;

trait HasConditionalVisibility
{
    protected ?array $visibleWhenRule = null;

    public function visibleWhen(string $column, mixed $value): static
    {
        $this->visibleWhenRule = is_array($value)
            ? ['column' => $column, 'values' => array_map('strval', $value)]
            : ['column' => $column, 'value' => (string)$value];

        return $this;
    }

    public function getVisibleWhen(): ?array
    {
        return $this->visibleWhenRule;
    }
}
