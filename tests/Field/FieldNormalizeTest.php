<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Text;
use PHPUnit\Framework\TestCase;

final class FieldNormalizeTest extends TestCase
{
    public function testBaseFieldDoesNotImplodeArrays(): void
    {
        self::assertSame('a', Text::make('Name', 'NAME')->normalize(['a', 'b']));
        self::assertSame(['a', 'b'], Select::make('Tags', 'TAGS')->multiple()->normalize(['a', 'b']));
    }
}
