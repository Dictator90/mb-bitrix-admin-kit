<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Page\OptionsPage as LegacyOptionsPage;
use MB\Bitrix\AdminKit\Pages\OptionsPage;
use PHPUnit\Framework\TestCase;

final class OptionsPageBackwardCompatibilityTest extends TestCase
{
    public function testLegacyOptionsPageExtendsStandaloneOptionsPage(): void
    {
        self::assertTrue(is_subclass_of(LegacyOptionsPage::class, OptionsPage::class));
    }
}
