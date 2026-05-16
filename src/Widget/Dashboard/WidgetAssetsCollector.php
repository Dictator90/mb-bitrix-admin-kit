<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Widget\Dashboard;

use MB\Bitrix\AdminKit\Contracts\UI\AssetAwareContract;

final class WidgetAssetsCollector
{
    /** @param iterable<mixed> $widgets */
    /** @return list<string> */
    public function collect(iterable $widgets): array
    {
        $extensions = [];
        foreach ($widgets as $widget) {
            if ($widget instanceof AssetAwareContract) {
                $extensions = array_merge($extensions, $widget->getRequiredExtensions());
            }
        }

        return array_values(array_unique($extensions));
    }
}
