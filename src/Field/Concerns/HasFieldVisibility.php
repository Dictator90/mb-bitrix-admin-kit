<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

use Closure;
use MB\Bitrix\AdminKit\Field\FieldConditionContext;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use MB\Support\Conditionable\ConditionTree;

trait HasFieldVisibility
{
    protected Closure|bool $visible = true;
    /** @var PageType[] */
    protected array $hiddenOn = [];
    protected ?array $visibleWhenRule = null;
    /** @var array<int, Closure|ConditionTree|array<string,mixed>> */
    protected array $visibleWhenConditions = [];

    /** @param string|list<string>|null $dependsOn */
    public function visible(bool|Closure $visible = true, string|array|null $dependsOn = null): static
    {
        $this->visible = $visible;
        if ($visible instanceof Closure && $dependsOn !== null) {
            $this->when($visible, static function (self $field): void {
                $field->visible(true);
            }, $dependsOn);
        }
        return $this;
    }
    /** @param string|list<string>|null $dependsOn */
    public function canSee(bool|Closure $condition = true, string|array|null $dependsOn = null): static
    {
        return $this->visible($condition, $dependsOn);
    }
    public function hideOn(PageType ...$pageTypes): static
    {
        $this->hiddenOn = array_merge($this->hiddenOn, $pageTypes);
        return $this;
    }
    public function showOn(PageType ...$pageTypes): static
    {
        $allPages = PageType::cases();
        $this->hiddenOn = array_filter($allPages, fn (PageType $pt) => !in_array($pt, $pageTypes, true));
        return $this;
    }

    public function isVisibleOn(PageType $pageType): bool
    {
        return $this->isVisibleFor(FieldConditionContext::fromArray([], $pageType), $pageType);
    }

    /** @param FieldConditionContext|array<string,mixed> $context */
    public function isVisibleFor(FieldConditionContext|array $context, ?PageType $pageType = null): bool
    {
        $ctx = is_array($context) ? FieldConditionContext::fromArray($context, $pageType) : $context;
        $actualPage = $pageType ?? $ctx->pageType ?? PageType::FORM;
        if (in_array($actualPage, $this->hiddenOn, true)) {
            return false;
        }
        if ($this->visible instanceof Closure && !$this->callConditionClosure($this->visible, $ctx, $this)) {
            return false;
        }
        if (is_bool($this->visible) && !$this->visible) {
            return false;
        }
        foreach ($this->visibleWhenConditions as $condition) {
            if (!$this->evaluateFieldCondition($condition, $ctx->data)) {
                return false;
            }
        }
        return true;
    }

    public function visibleWhen(string|ConditionTree|Closure $condition, ?string $operator = null, mixed $value = null, bool $reactive = false): static
    {
        $normalized = $this->normalizeCondition($condition, $operator, $value);
        $this->visibleWhenConditions[] = $normalized;
        if (is_array($normalized)) {
            $this->visibleWhenRule = is_array($normalized['value']) ? ['column' => $normalized['column'], 'operator' => $normalized['operator'], 'values' => array_map('strval', $normalized['value'])] : ['column' => $normalized['column'], 'operator' => $normalized['operator'], 'value' => (string) $normalized['value']];
        } else {
            $this->visibleWhenRule = null;
        }
        if ($reactive && is_array($normalized)) {
            $this->visible($this->conditionToClosure($condition, $operator, $value), $normalized['column']);
        }
        return $this;
    }

    public function getVisibleWhen(): ?array
    {
        return $this->visibleWhenRule;
    }
}
