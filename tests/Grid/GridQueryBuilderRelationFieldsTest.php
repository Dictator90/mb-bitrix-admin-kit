<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Field\HasMany;
use MB\Bitrix\AdminKit\Field\HasOne;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class GridQueryBuilderRelationFieldsTest extends TestCase
{
    public function testRelationColumnsExcludedAndLocalKeysIncluded(): void
    {
        $resource = new class () extends ProductResource {
            public function indexFields(): iterable
            {
                return [
                    Text::make('Name', 'NAME'),
                    HasMany::make('Sites', 'SITES')->table(RelationSiteTable::class)->foreignKey('COOKIE_ID')->localKey('ID')->value('SITE_ID'),
                    HasOne::make('Group', 'GROUP_NAME')->table(RelationSiteTable::class)->foreignKey('ID')->localKey('GROUP_ID')->value('SITE_ID'),
                ];
            }
        };

        $params = (new GridQueryBuilder())->build($resource, GridContext::make($resource));

        self::assertSame(['NAME', 'ID', 'GROUP_ID'], $params['select']);
    }
}
