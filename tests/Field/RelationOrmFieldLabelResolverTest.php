<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Relation\RelationOrmFieldLabelResolver;
use MB\Bitrix\AdminKit\Tests\Fixtures\LabelResolverTableFake;
use PHPUnit\Framework\TestCase;

final class RelationOrmFieldLabelResolverTest extends TestCase
{
    public function testResolvesTitleFromOrmEntity(): void
    {
        $label = RelationOrmFieldLabelResolver::resolve(LabelResolverTableFake::class, 'USER_ID');

        self::assertSame('User identifier', $label);
    }

    public function testFallsBackToColumnNameWhenFieldMissing(): void
    {
        $label = RelationOrmFieldLabelResolver::resolve(LabelResolverTableFake::class, 'UNKNOWN');

        self::assertSame('UNKNOWN', $label);
    }

    public function testResolvesIdColumnTitleFromOrmEntity(): void
    {
        $label = RelationOrmFieldLabelResolver::resolve(LabelResolverTableFake::class, 'ID');

        self::assertSame('Record identifier', $label);
    }

    public function testNormalizeTitleExtractsMessageFromArray(): void
    {
        $label = RelationOrmFieldLabelResolver::normalizeTitle(['MESSAGE' => 'User ID'], 'USER_ID');

        self::assertSame('User ID', $label);
    }

    public function testNormalizeTitleUsesFallbackForObjectWithoutStringRepresentation(): void
    {
        $label = RelationOrmFieldLabelResolver::normalizeTitle(new \stdClass(), 'USER_ID');

        self::assertSame('USER_ID', $label);
    }
}
