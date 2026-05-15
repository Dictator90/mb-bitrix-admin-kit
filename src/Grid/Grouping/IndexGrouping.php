<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Grid\Grouping;

use Closure;
use LogicException;

final class IndexGrouping
{
    /** @var class-string|null */
    private ?string $resourceClass = null;
    private ?string $foreignKey = null;
    private string $ownerKey = 'ID';
    private ?string $parentKey = null;
    private string|Closure|null $label = null;
    private ?string $labelColumn = null;
    /** @var array<string,string> */
    private array $order = [];
    private bool $expand = false;
    private bool $showUngrouped = true;
    private string|Closure|null $ungroupedLabel = null;

    public static function make(): self
    {
        return new self();
    }

    /** @param class-string $resourceClass */
    public function resource(string $resourceClass): self
    {
        $this->resourceClass = $resourceClass;

        return $this;
    }

    /** @return class-string */
    public function resourceClass(): string
    {
        if ($this->resourceClass === null || $this->resourceClass === '') {
            throw new LogicException('Index grouping resource class is required.');
        }

        return $this->resourceClass;
    }

    public function foreignKey(?string $column = null): self|string
    {
        if (func_num_args() === 0) {
            if ($this->foreignKey === null || $this->foreignKey === '') {
                throw new LogicException('Index grouping foreign key is required.');
            }

            return $this->foreignKey;
        }

        $this->foreignKey = (string)$column;

        return $this;
    }

    public function ownerKey(string $column = 'ID'): self|string
    {
        if (func_num_args() === 0) {
            return $this->ownerKey;
        }

        $this->ownerKey = $column;

        return $this;
    }

    public function parentKey(?string $column = null): self|string|null
    {
        if (func_num_args() === 0) {
            return $this->parentKey;
        }

        $this->parentKey = $column;

        return $this;
    }

    public function label(string|Closure|null $label = null): self|string|Closure|null
    {
        if (func_num_args() === 0) {
            return $this->label;
        }

        if ($label !== null) {
            $this->label = $label;
        }

        return $this;
    }

    public function labelColumn(?string $column = null): self|string|null
    {
        if (func_num_args() === 0) {
            return $this->labelColumn;
        }

        $this->labelColumn = $column;

        return $this;
    }

    /**
     * @param array<string,string>|null $order
     * @return self|array<string,string>
     */
    public function order(?array $order = null): self|array
    {
        if (func_num_args() === 0) {
            return $this->order;
        }

        $this->order = $order ?? [];

        return $this;
    }

    public function expand(?bool $expand = null): self|bool
    {
        if (func_num_args() === 0) {
            return $this->expand;
        }

        $this->expand = (bool)$expand;

        return $this;
    }

    public function showUngrouped(?bool $show = null): self|bool
    {
        if (func_num_args() === 0) {
            return $this->showUngrouped;
        }

        $this->showUngrouped = (bool)$show;

        return $this;
    }

    public function ungroupedLabel(string|Closure|null $label = null): self|string|Closure|null
    {
        if (func_num_args() === 0) {
            return $this->ungroupedLabel;
        }

        $this->ungroupedLabel = $label;

        return $this;
    }
}
