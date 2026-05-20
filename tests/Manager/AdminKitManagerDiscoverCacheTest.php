<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Manager;

use MB\Bitrix\AdminKit\Manager\AdminKitManager;
use MB\Bitrix\AdminKit\Manager\AdminKitScope;
use PHPUnit\Framework\TestCase;

final class AdminKitManagerDiscoverCacheTest extends TestCase
{
    public function testRegistryInstanceIsReusedForRepeatedDiscoverCalls(): void
    {
        $scope = AdminKitScope::fromDirectory(__DIR__ . '/../Fixtures/empty-discovery', 'test.discover.cache');
        $manager = new AdminKitManager($scope);

        $first = $manager->registry();
        $second = $manager->registry();

        self::assertSame($first, $second);
    }

    public function testLegacyPageAliasesAreLoadable(): void
    {
        self::assertTrue(class_exists(\MB\Bitrix\AdminKit\Page\IndexPage::class));
        self::assertTrue(class_exists(\MB\Bitrix\AdminKit\Page\FormPage::class));
        self::assertTrue(class_exists(\MB\Bitrix\AdminKit\Page\DetailPage::class));
        self::assertTrue(is_subclass_of(\MB\Bitrix\AdminKit\Page\IndexPage::class, \MB\Bitrix\AdminKit\Page\Crud\IndexPage::class));
    }
}
