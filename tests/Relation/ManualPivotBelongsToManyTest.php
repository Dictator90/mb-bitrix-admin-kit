<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use MB\Bitrix\AdminKit\Relation\RelationMetadataResolver;
use MB\Bitrix\AdminKit\Relation\RuntimeRelationRegistrar;
use PHPUnit\Framework\TestCase;

final class ManualPivotBelongsToManyTest extends TestCase
{
    public function testBelongsToManyWithoutMediatorReferencesSkipsRuntimeRegistration(): void
    {
        FakeRegisterOwnerTable::$entity = new FakeRegisterEntity();

        $field = BelongsToMany::make('Sites', 'SITES')
            ->relatedTable(FakeRelatedTable::class)
            ->pivotTable(FakeSimplePivotTable::class)
            ->foreignPivotKey('EVENT_MESSAGE_ID')
            ->relatedPivotKey('SITE_ID')
            ->saveUsingManualSync();

        $registered = (new RuntimeRelationRegistrar())->register(FakeRegisterOwnerTable::class, $field);

        self::assertFalse($registered);
        self::assertSame([], FakeRegisterOwnerTable::$entity->added);
    }

    public function testRelationSelectsOmitsManualPivotRelationName(): void
    {
        $field = BelongsToMany::make('Sites', 'SITES')
            ->relatedTable(FakeRelatedTable::class)
            ->pivotTable(FakeSimplePivotTable::class)
            ->foreignPivotKey('EVENT_MESSAGE_ID')
            ->relatedPivotKey('SITE_ID');

        $select = (new RelationMetadataResolver())->relationSelects(FakeRegisterOwnerTable::class, [$field]);

        self::assertSame(['*'], $select);
    }
}

/** Pivot without Reference fields (like EventMessageSiteTable). */
final class FakeSimplePivotTable
{
    public static function getList(array $params = []): FakePivotListResult
    {
        return new FakePivotListResult($params);
    }
}

final class FakePivotListResult
{
    /** @param array<string,mixed> $params */
    public function __construct(private array $params)
    {
    }

    public function fetch(): ?array
    {
        return null;
    }
}
