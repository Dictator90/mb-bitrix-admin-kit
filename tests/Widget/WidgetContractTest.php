<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Widget;

use MB\Bitrix\AdminKit\Component\Layout\AbstractLayoutComponent;
use MB\Bitrix\AdminKit\Contracts\UI\AssetAwareContract;
use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;
use MB\Bitrix\AdminKit\Widget\CountWidget;
use PHPUnit\Framework\TestCase;

final class WidgetContractTest extends TestCase
{
    public function testWidgetIsLeafComponentWithoutLayoutInheritance(): void
    {
        $widget = CountWidget::make('X', \Bitrix\Main\ORM\Data\DataManager::class)->icon('--cart')->span(4);

        self::assertInstanceOf(ComponentContract::class, $widget);
        self::assertInstanceOf(AssetAwareContract::class, $widget);
        self::assertNotInstanceOf(AbstractLayoutComponent::class, $widget);
    }
}
