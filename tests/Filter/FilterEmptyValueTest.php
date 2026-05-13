<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Filter;

use MB\Bitrix\AdminKit\Filter\Types\NumberFilter;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use PHPUnit\Framework\TestCase;

final class FilterEmptyValueTest extends TestCase
{
    public function testFiltersKeepZeroValues(): void
    {
        self::assertSame(['%NAME' => '0'], TextFilter::make('Name', 'NAME')->apply([], '0'));
        self::assertSame(['SORT' => 0], NumberFilter::make('Sort', 'SORT')->apply([], 0));
    }
}
