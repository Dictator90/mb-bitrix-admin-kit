<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Filter;

use MB\Bitrix\AdminKit\Filter\Types\CheckboxFilter;
use MB\Bitrix\AdminKit\Filter\Types\NumberFilter;
use MB\Bitrix\AdminKit\Filter\Types\TextFilter;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class EmptyFilterValueTest extends TestCase
{
    public function testItSkipsOnlyEmptyValues(): void
    {
        $resource = new class extends ProductResource {
            public function filters(): iterable
            {
                return [
                    TextFilter::make('Name', 'NAME'),
                    NumberFilter::make('Zero', 'ZERO'),
                    TextFilter::make('String Zero', 'STRING_ZERO'),
                    CheckboxFilter::make('Boolean', 'BOOLEAN'),
                    TextFilter::make('Empty Array', 'EMPTY_ARRAY'),
                ];
            }
        };

        $params = (new GridQueryBuilder())->build($resource, GridContext::make($resource, null, [
            'filter' => ['NAME' => '', 'ZERO' => 0, 'STRING_ZERO' => '0', 'BOOLEAN' => false, 'EMPTY_ARRAY' => []],
        ]));

        self::assertSame(['ZERO' => 0, '%STRING_ZERO' => '0', 'BOOLEAN' => false], $params['filter']);
    }
}
