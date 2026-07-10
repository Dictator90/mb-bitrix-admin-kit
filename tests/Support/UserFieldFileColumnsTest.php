<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Support;

use MB\Bitrix\AdminKit\Support\UserFieldFileColumns;
use MB\Bitrix\AdminKit\Tests\Support\Fixtures\NonUfDataManagerStub;
use PHPUnit\Framework\TestCase;

final class UserFieldFileColumnsTest extends TestCase
{
    public function testClassWithoutUserFieldsResolvesToEmptyMap(): void
    {
        self::assertSame([], UserFieldFileColumns::forDataManager(\stdClass::class));
    }

    public function testUnknownUfEntityResolvesToEmptyMap(): void
    {
        self::assertSame([], UserFieldFileColumns::forDataManager(NonUfDataManagerStub::class));
    }

    public function testResultIsCachedAndStableAcrossCalls(): void
    {
        $first = UserFieldFileColumns::forDataManager(NonUfDataManagerStub::class);
        $second = UserFieldFileColumns::forDataManager(NonUfDataManagerStub::class);

        self::assertSame($first, $second);
    }
}
