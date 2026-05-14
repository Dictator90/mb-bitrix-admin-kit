<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Database\Performance\ArrayTtlCache;
use MB\Bitrix\AdminKit\Field\Select;
use PHPUnit\Framework\TestCase;

final class SelectOptionsCacheTest extends TestCase
{
    public function testSelectOptionsCanBeCached(): void
    {
        ArrayTtlCache::clear();
        $calls = 0;
        $field = Select::make('Status', 'STATUS')->options(function () use (&$calls): array {
            $calls++;
            return ['Y' => 'Active'];
        })->cache(3600);

        self::assertSame(['Y' => 'Active'], $field->getOptions());
        self::assertSame(['Y' => 'Active'], $field->getOptions());
        self::assertSame(1, $calls);
    }
}
