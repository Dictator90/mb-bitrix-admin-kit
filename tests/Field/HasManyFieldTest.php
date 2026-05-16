<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\HasMany;
use MB\Bitrix\AdminKit\Tests\Grid\RelationSiteTable;
use PHPUnit\Framework\TestCase;

final class HasManyFieldTest extends TestCase
{
    public function testConfigGetters(): void
    {
        $field = HasMany::make('Sites', 'SITES')->table(RelationSiteTable::class)->foreignKey('COOKIE_ID')->localKey('ID')->value('SITE_ID');

        self::assertTrue($field->isRelationField());
        self::assertTrue($field->isToMany());
        self::assertSame(RelationSiteTable::class, $field->relationTableClass());
        self::assertSame('COOKIE_ID', $field->relationForeignKey());
        self::assertSame('ID', $field->relationLocalKey());
        self::assertSame('SITE_ID', $field->relationValue());
        self::assertSame([], $field->relationDefault());
    }
}
