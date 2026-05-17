<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Action;

use LogicException;
use MB\Bitrix\AdminKit\Contracts\Action\BulkPanelItemContract;

final class BulkActionDropdown implements BulkPanelItemContract
{
    protected string $id;
    protected string $label;
    protected string $group = 'default';
    protected ?string $groupLabel = null;
    protected int $groupSort = 100;
    protected int $sort = 100;
    protected ?string $icon = null;
    protected ?string $buttonClass = null;
    protected ?string $title = null;
    protected bool $multiple = false;
    protected ?string $placeholder = null;
    protected string $placeholderValue = '';
    protected bool $showPlaceholder = true;

    /** @var list<BulkAction> */
    protected array $items = [];

    public function __construct(string $id, ?string $label = null)
    {
        $this->id = $id;
        $this->label = $label ?? $id;
    }

    public static function make(string $id, ?string $label = null): static
    {
        return new static($id, $label);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function group(string $group, ?string $label = null, ?int $sort = null): static
    {
        $this->group = $group;
        $this->groupLabel = $label;

        if ($sort !== null) {
            $this->groupSort = $sort;
        }

        return $this;
    }

    public function groupSort(int $sort): static
    {
        $this->groupSort = $sort;

        return $this;
    }

    public function groupLabel(?string $label): static
    {
        $this->groupLabel = $label;

        return $this;
    }

    public function sort(int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function buttonClass(?string $class): static
    {
        $this->buttonClass = $class;

        return $this;
    }

    public function class(string $class): static
    {
        $this->buttonClass = $class;

        return $this;
    }

    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        if ($multiple) {
            throw new LogicException('Multiple dropdown bulk actions are not supported yet.');
        }

        $this->multiple = false;

        return $this;
    }

    public function placeholder(?string $text, string $value = ''): static
    {
        if ($text === null) {
            return $this->withoutPlaceholder();
        }

        $this->placeholder = $text;
        $this->placeholderValue = $value;
        $this->showPlaceholder = true;

        return $this;
    }

    public function withoutPlaceholder(): static
    {
        $this->placeholder = null;
        $this->placeholderValue = '';
        $this->showPlaceholder = false;

        return $this;
    }

    public function item(BulkAction $action): static
    {
        $this->items[] = $action;

        return $this;
    }

    /** @param iterable<BulkAction> $actions */
    public function items(iterable $actions): static
    {
        foreach ($actions as $action) {
            $this->item($action);
        }

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    public function getGroupLabel(): ?string
    {
        return $this->groupLabel;
    }

    public function getGroupSort(): int
    {
        return $this->groupSort;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getButtonClass(): ?string
    {
        return $this->buttonClass;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function shouldShowPlaceholder(): bool
    {
        return $this->showPlaceholder;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder ?? $this->getLabel();
    }

    public function getPlaceholderValue(): string
    {
        return $this->placeholderValue;
    }

    /** @return list<BulkAction> */
    public function getItems(): array
    {
        return $this->items;
    }

    public function isVisible(): bool
    {
        foreach ($this->items as $item) {
            if ($item->isVisible()) {
                return true;
            }
        }

        return false;
    }
}
