<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Filter;

use MB\Bitrix\AdminKit\Filter\Types\CallbackFilter;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class CallbackFilterTest extends TestCase
{
    public function testItAppliesCallbackToOrmFilter(): void
    {
        $resource = new class extends ProductResource {
            public function filters(): iterable
            {
                return [CallbackFilter::make('Search', 'SEARCH')->apply(function (array $filter, mixed $value): array {
                    $filter[] = ['LOGIC' => 'OR', '%NAME' => $value, '%CODE' => $value];

                    return $filter;
                })];
            }
        };

        $params = (new GridQueryBuilder())->build($resource, GridContext::make($resource, null, [
            'filter' => ['SEARCH' => 'phone'],
        ]));

        self::assertSame([['LOGIC' => 'OR', '%NAME' => 'phone', '%CODE' => 'phone']], $params['filter']);
    }
}
