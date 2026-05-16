<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Widget\Dashboard;

use MB\Bitrix\AdminKit\Support\AdminCollection;

final class WidgetCollection
{
    /** @param iterable<mixed> $widgets */
    public function __construct(private readonly iterable $widgets)
    {
    }

    /** @return list<mixed> */
    public function all(): array
    {
        return array_values(AdminCollection::make($this->widgets)->all());
    }
}
