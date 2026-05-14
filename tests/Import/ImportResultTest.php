<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Import;

use MB\Bitrix\AdminKit\Import\ImportResult;
use PHPUnit\Framework\TestCase;

final class ImportResultTest extends TestCase
{
    public function testItAccumulatesCountersAndErrorsImmutably(): void
    {
        $result = ImportResult::empty()->withTotal(2)->withCreated()->withUpdated()->withSkipped()->addError(2, 'Invalid');

        self::assertSame(2, $result->total);
        self::assertSame(1, $result->created);
        self::assertSame(1, $result->updated);
        self::assertSame(1, $result->skipped);
        self::assertFalse($result->isSuccess());
        self::assertSame([2 => ['Invalid']], $result->errors);
    }
}
