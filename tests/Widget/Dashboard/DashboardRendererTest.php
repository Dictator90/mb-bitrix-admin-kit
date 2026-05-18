<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Widget\Dashboard;

use MB\Bitrix\AdminKit\Widget\CountWidget;
use MB\Bitrix\AdminKit\Widget\Dashboard\DashboardRenderer;
use MB\Bitrix\AdminKit\Widget\Dashboard\WidgetAssetsCollector;
use PHPUnit\Framework\TestCase;

final class DashboardRendererTest extends TestCase
{
    public function testCollectsExtensionsAndRendersGridWithoutInlineCss(): void
    {
        $widgets = [
            CountWidget::make('Count', \Bitrix\Main\ORM\Data\DataManager::class),
            '<div>raw</div>',
        ];

        $extensions = (new WidgetAssetsCollector())->collect($widgets);
        $html = (new DashboardRenderer())->render($widgets);

        self::assertIsArray($extensions);
        self::assertStringContainsString('adminkit-dashboard', $html);
        self::assertStringNotContainsString('<style>', $html);
    }
}
