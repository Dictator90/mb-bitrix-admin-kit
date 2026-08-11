<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\IblockSectionElementSelect;
use PHPUnit\Framework\TestCase;

final class IblockSectionElementSelectDecodeTest extends TestCase
{
    public function testDecodesTypePrefixesKeepingOrder(): void
    {
        self::assertSame(
            [
                ['type' => 'section', 'id' => 1],
                ['type' => 'element', 'id' => 20],
                ['type' => 'section', 'id' => 2],
            ],
            IblockSectionElementSelect::decode(['s:1', 'e:20', 's:2'])
        );
    }

    public function testDecodesCommaSeparatedString(): void
    {
        self::assertSame(
            [
                ['type' => 'section', 'id' => 1],
                ['type' => 'element', 'id' => 20],
            ],
            IblockSectionElementSelect::decode('s:1, e:20')
        );
    }

    public function testTreatsBareNumberAsElementForBackwardCompatibility(): void
    {
        self::assertSame([['type' => 'element', 'id' => 12]], IblockSectionElementSelect::decode(['12']));
        self::assertSame([['type' => 'element', 'id' => 12]], IblockSectionElementSelect::decode(12));
    }

    public function testReturnsEmptyListForEmptyValues(): void
    {
        self::assertSame([], IblockSectionElementSelect::decode(null));
        self::assertSame([], IblockSectionElementSelect::decode(''));
        self::assertSame([], IblockSectionElementSelect::decode([]));
        self::assertSame([], IblockSectionElementSelect::decode(['', '  ']));
    }

    public function testSkipsNonPositiveAndMalformedValues(): void
    {
        self::assertSame([], IblockSectionElementSelect::decode(['s:0', 'e:0', 's:', 'e:', 'abc', 's:abc']));
    }

    public function testKeepsDuplicatesSoConsumersDecideHowToCollapseThem(): void
    {
        self::assertSame(
            [
                ['type' => 'section', 'id' => 1],
                ['type' => 'section', 'id' => 1],
            ],
            IblockSectionElementSelect::decode(['s:1', 's:1'])
        );
    }
}
