<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Widget;

use MB\Bitrix\AdminKit\Widget\ChartWidget;
use MB\Bitrix\AdminKit\Widget\GraphWidget;
use PHPUnit\Framework\TestCase;

final class ChartWidgetTest extends TestCase
{
    public function testChartWidgetUsesDataConfigAndNoCdnScript(): void
    {
        $widget = ChartWidget::make('Orders', 'bar')->data([
            ['category' => 'Jan', 'value' => 10],
        ]);
        $html = $widget->render();

        self::assertStringContainsString('data-adminkit-chart=', $html);
        self::assertStringNotContainsString('cdn.jsdelivr.net', $html);
        self::assertContains('mb.admin.kit', $widget->getRequiredExtensions());
    }

    public function testGraphWidgetActsAsCompatibilityAlias(): void
    {
        self::assertInstanceOf(ChartWidget::class, GraphWidget::make('Legacy'));
    }
}
