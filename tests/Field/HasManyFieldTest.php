<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Relation\HasMany;
use MB\Bitrix\AdminKit\Tests\Fixtures\LabelResolverTableFake;
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

    public function testTablePreviewRendersScalarRelationRows(): void
    {
        $field = HasMany::make('Links', 'USER_GROUP')->asTable();
        $html = $field->renderFormField([
            ['ID' => 1, 'USER_ID' => 11, 'GROUP_ID' => 5],
            ['ID' => 2, 'USER_ID' => 12, 'GROUP_ID' => 5],
        ]);

        self::assertStringContainsString('adminkit-relation-tilegrid', $html);
        self::assertStringContainsString('ui.tilegrid', $html);
        self::assertStringContainsString('RelationTileGrid', $html);
        self::assertStringContainsString('USER_ID', $html);
    }

    public function testAsTableAcceptsColumnListAndCustomLabels(): void
    {
        $field = HasMany::make('Links', 'USER_GROUP')
            ->relatedTable(LabelResolverTableFake::class)
            ->asTable([
                'USER_ID' => 'Пользователь',
                'ID',
            ]);

        self::assertSame(['USER_ID', 'ID'], $field->getTablePreviewColumnNames());
        self::assertSame(['USER_ID' => 'Пользователь', 'ID' => null], $field->getTableColumns());
    }

    public function testAsTableRendersOnlyConfiguredColumnsWithLabels(): void
    {
        $field = HasMany::make('Links', 'USER_GROUP')
            ->relatedTable(LabelResolverTableFake::class)
            ->asTable([
                'USER_ID' => 'Пользователь',
                'ID',
            ]);

        $html = $field->renderFormField([
            ['ID' => 1, 'USER_ID' => 11, 'GROUP_ID' => 5],
        ]);

        self::assertStringContainsString('Пользователь', $html);
        self::assertStringContainsString('Record identifier', $html);
        self::assertStringNotContainsString('GROUP_ID', $html);
    }
}
