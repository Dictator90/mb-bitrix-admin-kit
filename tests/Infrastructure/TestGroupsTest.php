<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Infrastructure;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

final class TestGroupsTest extends TestCase
{
    #[Group('Unit')]
    public function testUnitGroupIsRunnable(): void
    {
        self::assertTrue(true);
    }

    #[Group('IntegrationLike')]
    public function testIntegrationLikeGroupWithoutRealBitrixIsRunnable(): void
    {
        self::assertTrue(function_exists('check_bitrix_sessid'));
    }

    #[Group('Compatibility')]
    public function testCompatibilityGroupIsRunnable(): void
    {
        self::assertTrue(class_exists(\MB\Bitrix\AdminKit\Resource\CrudResource::class));
    }
}
