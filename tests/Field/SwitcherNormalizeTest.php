<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Switcher;
use PHPUnit\Framework\TestCase;

final class SwitcherNormalizeTest extends TestCase
{
    public function testMissingPostValueNormalizesToUnchecked(): void
    {
        $field = Switcher::make('Active', 'ACTIVE')->values('Y', 'N');

        self::assertSame('N', $field->normalize(null));
        self::assertSame('N', $field->serializePostValue(null));
    }

    public function testCheckedValueIsPreserved(): void
    {
        $field = Switcher::make('Active', 'ACTIVE')->values('Y', 'N');

        self::assertSame('Y', $field->normalize('Y'));
    }

    public function testBooleanTrueIsTreatedAsChecked(): void
    {
        $field = Switcher::make('Active', 'ACTIVE')->values('Y', 'N');

        self::assertTrue($field->isCheckedState(true));
        self::assertSame('Y', $field->normalize(true));
    }
}
