<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Widget;

use MB\Bitrix\AdminKit\Widget\CountWidget;
use PHPUnit\Framework\TestCase;

final class CountWidgetTest extends TestCase
{
    public function testCustomValueCallbackAndMissingClassFallback(): void
    {
        $custom = CountWidget::make('Revenue', \Bitrix\Main\ORM\Data\DataManager::class)
            ->value(static fn (): string => '42');
        self::assertStringContainsString('42', $custom->render());

        $fallback = CountWidget::make('Missing', \Bitrix\Main\ORM\Data\DataManager::class);
        self::assertStringContainsString('—', $fallback->render());
    }
}
