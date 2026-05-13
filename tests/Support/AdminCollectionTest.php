<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Support;

use MB\Bitrix\AdminKit\Support\AdminCollection;
use MB\Support\Collection;
use PHPUnit\Framework\TestCase;

final class AdminCollectionTest extends TestCase
{
    public function testItReturnsExistingCollection(): void
    {
        $collection = new Collection(['a' => 1]);
        self::assertSame($collection, AdminCollection::make($collection));
    }

    public function testItAcceptsIterable(): void
    {
        self::assertSame(['a' => 1], AdminCollection::make(new \ArrayIterator(['a' => 1]))->all());
    }
}
