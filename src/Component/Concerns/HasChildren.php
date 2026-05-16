<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Concerns;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;

trait HasChildren
{
    /** @var array<int, FieldContract|ComponentContract> */
    protected array $children = [];

    /** @param array<int, FieldContract|ComponentContract> $children */
    public function children(array $children): static
    {
        $this->children = $children;

        return $this;
    }

    /** @param FieldContract|ComponentContract $child */
    public function add(mixed $child): static
    {
        $this->children[] = $child;

        return $this;
    }
}
