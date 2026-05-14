<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class GridQueryBuilderSelectTest extends TestCase
{
    public function testItMergesIndexFieldsDefaultSelectAndIndexSelectWithoutComputedColumns(): void
    {
        $resource = new class extends ProductResource {
            public function indexFields(): iterable
            {
                return [
                    Text::make('Name', 'NAME'),
                    Text::make('Computed', 'FULL_NAME')->computed(fn(array $row): string => 'computed'),
                ];
            }

            public function defaultSelect(): array
            {
                return ['CODE'];
            }

            public function indexSelect(GridContext $context): array
            {
                return ['USER_NAME'];
            }
        };

        $params = (new GridQueryBuilder())->build($resource, GridContext::make($resource));

        self::assertSame(['NAME', 'CODE', 'USER_NAME', 'ID'], $params['select']);
    }
}
