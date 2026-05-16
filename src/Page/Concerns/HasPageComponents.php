<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Concerns;

use MB\Bitrix\AdminKit\Contracts\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\FieldContract;

trait HasPageComponents
{
    /** @return iterable<ComponentContract> */
    protected function components(): iterable
    {
        return [];
    }

    /** @return array<int,ComponentContract> */
    protected function getComponents(): array
    {
        return array_values(array_filter(
            iterator_to_array($this->components()),
            static fn (mixed $component): bool => $component instanceof ComponentContract,
        ));
    }

    /** @return array<int,FieldContract> */
    protected function extractFields(iterable $items): array
    {
        $fields = [];
        foreach ($items as $item) {
            if ($item instanceof FieldContract) {
                $fields[] = $item;
            }
        }

        return $fields;
    }

    /** @return array<int,FieldContract> */
    protected function collectFields(): array
    {
        return $this->extractFields($this->components());
    }
}
