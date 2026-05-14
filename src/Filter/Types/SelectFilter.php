<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Filter\Types;

use MB\Bitrix\AdminKit\Filter\Filter;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Support\AdminCollection;

class SelectFilter extends Filter
{
    protected array $options = [];
    protected bool $multiple = true;

    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function exact(): static
    {
        $this->multiple = false;

        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function getType(): string
    {
        return 'list';
    }

    public function getFilterFieldConfig(): array
    {
        $config = parent::getFilterFieldConfig();
        $config['params'] = ['multiple' => $this->multiple ? 'Y' : 'N'];

        return $config;
    }

    public function prepareFieldData(): array
    {
        $items = [];
        foreach (AdminCollection::make($this->options)->all() as $value => $label) {
            $items[] = [
                'NAME' => $label,
                'VALUE' => (string)$value,
            ];
        }

        return ['items' => $items];
    }

    protected function applyValue(array $filter, mixed $value, ?GridContext $context): array
    {
        $filter[$this->column] = $value;

        return $filter;
    }
}
