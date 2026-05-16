<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

use Closure;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Support\Conditionable\ConditionTree;

trait HasFieldVisibility
{
    protected Closure|bool $visible = true;

    /** @var PageType[] */
    protected array $hiddenOn = [];

    protected ?array $visibleWhenRule = null;

    public function visible(Closure|bool $condition): static
    {
        $this->visible = $condition;

        return $this;
    }

    public function hideOn(PageType ...$pageTypes): static
    {
        $this->hiddenOn = array_merge($this->hiddenOn, $pageTypes);

        return $this;
    }

    public function showOn(PageType ...$pageTypes): static
    {
        $allPages = PageType::cases();
        $this->hiddenOn = array_filter(
            $allPages,
            fn (PageType $pt) => !in_array($pt, $pageTypes, true),
        );

        return $this;
    }

    public function isVisibleOn(PageType $pageType): bool
    {
        if (in_array($pageType, $this->hiddenOn, true)) {
            return false;
        }

        if ($this->visible instanceof Closure) {
            return (bool)($this->visible)();
        }

        return $this->visible;
    }

    public function visibleWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null): static
    {
        if (is_string($condition) && $value === null && $operator !== null
            && !in_array($operator, ['=', '!=', 'in', 'not in'], true)) {
            $value = $operator;
            $operator = '=';
        }

        $normalized = $this->normalizeCondition($condition, $operator, $value);
        if (is_array($normalized)) {
            $this->visibleWhenRule = is_array($normalized['value'])
                ? ['column' => $normalized['column'], 'operator' => $normalized['operator'], 'values' => array_map('strval', $normalized['value'])]
                : ['column' => $normalized['column'], 'operator' => $normalized['operator'], 'value' => (string)$normalized['value']];
        } else {
            $this->visibleWhenRule = null;
        }

        return $this;
    }

    public function getVisibleWhen(): ?array
    {
        return $this->visibleWhenRule;
    }
}
