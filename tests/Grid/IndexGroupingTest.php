<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Grid\Grouping\IndexGrouping;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class IndexGroupingTest extends TestCase
{
    public function testDefaults(): void
    {
        $grouping = IndexGrouping::make()->resource(ProductResource::class)->foreignKey('GROUP_ID');

        self::assertSame(ProductResource::class, $grouping->resourceClass());
        self::assertSame('GROUP_ID', $grouping->foreignKey());
        self::assertSame('ID', $grouping->ownerKey());
        self::assertNull($grouping->parentKey());
        self::assertNull($grouping->label());
        self::assertNull($grouping->labelColumn());
        self::assertSame([], $grouping->order());
        self::assertFalse($grouping->expand());
        self::assertTrue($grouping->showUngrouped());
    }

    public function testFluentMethodsReturnSelfAndStoreValues(): void
    {
        $label = static fn (array $row): string => (string)$row['NAME'];
        $ungrouped = static fn (): string => 'No group';
        $grouping = IndexGrouping::make();

        self::assertSame($grouping, $grouping->resource(ProductResource::class));
        self::assertSame($grouping, $grouping->foreignKey('GROUP_ID'));
        self::assertSame($grouping, $grouping->ownerKey('UF_ID'));
        self::assertSame($grouping, $grouping->parentKey('PARENT_ID'));
        self::assertSame($grouping, $grouping->label($label));
        self::assertSame($grouping, $grouping->labelColumn('NAME'));
        self::assertSame($grouping, $grouping->order(['SORT' => 'ASC']));
        self::assertSame($grouping, $grouping->expand(true));
        self::assertSame($grouping, $grouping->showUngrouped(false));
        self::assertSame($grouping, $grouping->ungroupedLabel($ungrouped));

        self::assertSame('UF_ID', $grouping->ownerKey());
        self::assertSame('PARENT_ID', $grouping->parentKey());
        self::assertSame($label, $grouping->label());
        self::assertSame('NAME', $grouping->labelColumn());
        self::assertSame(['SORT' => 'ASC'], $grouping->order());
        self::assertTrue($grouping->expand());
        self::assertFalse($grouping->showUngrouped());
        self::assertSame($ungrouped, $grouping->ungroupedLabel());
    }

    public function testStringLabelIsStored(): void
    {
        $grouping = IndexGrouping::make()->label('NAME');

        self::assertSame('NAME', $grouping->label());
    }
}
