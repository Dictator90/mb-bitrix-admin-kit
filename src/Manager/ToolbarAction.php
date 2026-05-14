<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Manager;

use MB\Bitrix\AdminKit\Support\AdminCondition;
use MB\Bitrix\AdminKit\Support\AdminString;
use MB\Support\Conditionable\ConditionTree;

final class ToolbarAction
{
    private mixed $visibility = null;

    public function __construct(
        private string $id,
        private string $label,
        private string $url = '#',
        private string $class = 'ui-btn ui-btn-light-border'
    ) {}

    public static function make(string $label, string $id = ''): self
    {
        return new self($id !== '' ? $id : AdminString::id('toolbar', $label), $label);
    }

    public function url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function class(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    public function canSee(bool|callable|ConditionTree $condition): self
    {
        $this->visibility = $condition;

        return $this;
    }

    /** @param array<string,mixed> $context */
    public function isVisible(array $context = []): bool
    {
        return AdminCondition::evaluate($this->visibility, $context);
    }

    public function render(): string
    {
        return '<a id="' . htmlspecialcharsbx($this->id) . '" class="' . htmlspecialcharsbx($this->class) . '" href="' . htmlspecialcharsbx($this->url) . '">' . htmlspecialcharsbx($this->label) . '</a>';
    }
}
