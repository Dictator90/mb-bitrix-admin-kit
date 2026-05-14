<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Filter;

use MB\Bitrix\AdminKit\Contracts\FilterContract;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Support\AdminString;

abstract class Filter implements FilterContract
{
    protected string $column;
    protected string $label;
    protected bool $default = true;

    public function __construct(string $label, ?string $column = null)
    {
        $this->label = $label;
        $this->column = $column ?? AdminString::safeKey($label);
    }

    public static function make(string $label, ?string $column = null): static
    {
        return new static($label, $column);
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function default(bool $default = true): static
    {
        $this->default = $default;

        return $this;
    }

    abstract public function getType(): string;

    public function getFilterFieldConfig(): array
    {
        return [
            'id' => AdminString::safeKey($this->column),
            'name' => $this->label,
            'type' => $this->getType(),
            'default' => $this->default,
        ];
    }

    public function prepareFieldData(): array
    {
        return [];
    }

    public function apply(mixed $filter, mixed $value = null): mixed
    {
        if (!is_array($filter)) {
            return $this;
        }

        return $this->applyValue($filter, $value, null);
    }

    public function applyToOrmFilter(array $filter, mixed $value, GridContext $context): array
    {
        return $this->applyValue($filter, $value, $context);
    }

    protected function applyValue(array $filter, mixed $value, ?GridContext $context): array
    {
        $filter[$this->column] = $value;

        return $filter;
    }
}
