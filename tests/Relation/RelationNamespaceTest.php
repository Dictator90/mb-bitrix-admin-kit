<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Relation;

use MB\Bitrix\AdminKit\Field\Relation\BelongsTo;
use PHPUnit\Framework\TestCase;

final class RelationNamespaceTest extends TestCase
{
    public function testRelationFieldsLiveInRelationNamespace(): void
    {
        self::assertTrue(class_exists(BelongsTo::class));
        self::assertSame('MB\\Bitrix\\AdminKit\\Field\\Relation\\BelongsTo', BelongsTo::class);
        self::assertFalse(class_exists('MB\\Bitrix\\AdminKit\\Field\\BelongsTo'));
    }
}
