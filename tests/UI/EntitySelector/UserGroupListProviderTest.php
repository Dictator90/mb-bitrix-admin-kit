<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\UI\EntitySelector;

use MB\Bitrix\AdminKit\UI\EntitySelector\UserGroupListProvider;
use PHPUnit\Framework\TestCase;

final class UserGroupListProviderTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(\Bitrix\UI\EntitySelector\BaseProvider::class)) {
            self::markTestSkipped('Bitrix EntitySelector is not available in the test runtime.');
        }
    }

    public function testEntityIdIsStable(): void
    {
        self::assertSame('user-group-list', UserGroupListProvider::ENTITY_ID);
    }

    public function testIsAvailableReturnsBoolean(): void
    {
        $provider = new UserGroupListProvider([]);

        self::assertIsBool($provider->isAvailable());
    }
}
