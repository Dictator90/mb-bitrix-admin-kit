<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\HasOne;
use MB\Bitrix\AdminKit\Tests\Grid\RelationSiteTable;
use PHPUnit\Framework\TestCase;

final class HasOneFieldTest extends TestCase
{
    public function testConfigGetters(): void
    {
        $field = HasOne::make('Site', 'SITE')->table(RelationSiteTable::class)->foreignKey('COOKIE_ID')->localKey('ID')->value('SITE_ID')->default('none');

        self::assertTrue($field->isRelationField());
        self::assertFalse($field->isToMany());
        self::assertSame('none', $field->relationDefault());
    }
}
