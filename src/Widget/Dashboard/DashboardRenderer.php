<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Widget\Dashboard;

use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;
use MB\Bitrix\AdminKit\Manager\AssetManager;

final class DashboardRenderer
{
    /** @param iterable<mixed> $widgets */
    public function render(iterable $widgets): string
    {
        $collection = (new WidgetCollection($widgets))->all();
        $extensions = (new WidgetAssetsCollector())->collect($collection);
        if ($extensions !== []) {
            (new AssetManager())->addExtensions($extensions)->load();
        }

        $html = '<div class="adminkit-dashboard">';
        foreach ($collection as $widget) {
            if ($widget instanceof ComponentContract) {
                $html .= $widget->render();
                continue;
            }

            $html .= '<div class="adminkit-dashboard__widget">' . (string)$widget . '</div>';
        }

        return $html . '</div>';
    }
}
