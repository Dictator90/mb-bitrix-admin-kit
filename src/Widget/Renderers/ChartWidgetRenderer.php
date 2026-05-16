<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Widget\Renderers;

final class ChartWidgetRenderer
{
    /** @param array<string,mixed> $config */
    public function render(string $title, string $widgetId, int $height, array $config): string
    {
        $label = htmlspecialcharsbx($title);
        $id = htmlspecialcharsbx($widgetId);
        $configJson = htmlspecialcharsbx((string)json_encode($config, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $heightPx = max(100, $height);

        return <<<HTML
<div class="adminkit-widget__header"><span class="adminkit-widget__title">{$label}</span></div>
<div class="adminkit-chart-widget__canvas" data-adminkit-chart-height="{$heightPx}">
    <canvas id="{$id}" data-adminkit-chart="{$configJson}" data-adminkit-chart-height="{$heightPx}"></canvas>
</div>
<script>
BX.ready(function () {
    if (window.MB && MB.AdminKit && MB.AdminKit.ChartWidget && typeof MB.AdminKit.ChartWidget.init === 'function') {
        MB.AdminKit.ChartWidget.init();
    }
});
</script>
HTML;
    }
}
