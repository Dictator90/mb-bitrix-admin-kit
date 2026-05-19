<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Field\BelongsTo;
use MB\Bitrix\AdminKit\Relation\RuntimeRelationBuilder;
use MB\Bitrix\AdminKit\Relation\RuntimeRelationRegistrar;
use PHPUnit\Framework\TestCase;

final class RuntimeRelationRegistrarTest extends TestCase
{
    public function testAddFieldCalledOnce(): void
    {
        FakeRegisterOwnerTable::$entity = new FakeRegisterEntity();
        $field = BelongsTo::make('Category', 'CATEGORY')->relatedTable(FakeRelatedTable::class)->foreignKey('CATEGORY_ID');

        (new RuntimeRelationRegistrar(new RuntimeRelationBuilder()))->register(FakeRegisterOwnerTable::class, $field);

        self::assertCount(1, FakeRegisterOwnerTable::$entity->added);
    }
}

final class FakeRegisterOwnerTable
{
    public static FakeRegisterEntity $entity;

    public static function getEntity(): FakeRegisterEntity
    {
        return self::$entity;
    }
}

final class FakeRegisterEntity
{
    public array $added = [];

    public function hasField(string $name): bool
    {
        return false;
    }

    public function addField(object $field): void
    {
        $this->added[] = $field;
    }
}
