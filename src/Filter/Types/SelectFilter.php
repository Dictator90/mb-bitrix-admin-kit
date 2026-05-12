<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Filter\Types;

use MB\Bitrix\AdminKit\Filter\Filter;

class SelectFilter extends Filter
{
    protected array $options = [];

    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    public function getType(): string
    {
        return 'list';
    }

    public function getFilterFieldConfig(): array
    {
        $config = parent::getFilterFieldConfig();
        $config['params'] = ['multiple' => 'Y'];

        return $config;
    }

    public function prepareFieldData(): array
    {
        $items = [];
        foreach ($this->options as $value => $label) {
            $items[] = [
                'NAME' => $label,
                'VALUE' => (string)$value,
            ];
        }

        return ['items' => $items];
    }

    public function apply(array $filter, mixed $value): array
    {
        if ($value !== '' && $value !== null) {
            $filter[$this->column] = $value;
        }

        return $filter;
    }
}
