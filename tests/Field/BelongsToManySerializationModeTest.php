<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Relation\BelongsToMany;
use PHPUnit\Framework\TestCase;

final class BelongsToManySerializationModeTest extends TestCase
{
    public function testLegacyCsvModeIsKept(): void
    {
        $field = BelongsToMany::make('Tags', 'TAG_IDS')->storedAsCsv();

        self::assertSame('1,2', $field->serializePostValue(['1', '2']));
    }

    public function testOrmModeReturnsArrayIds(): void
    {
        $field = BelongsToMany::make('Sections', 'SECTIONS')->relation('SECTIONS');

        self::assertSame(['1', '2'], $field->serializePostValue(['1', '2']));
    }
}
