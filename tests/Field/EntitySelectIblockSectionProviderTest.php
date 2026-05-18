<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\EntitySelect;
use MB\Bitrix\AdminKit\UI\EntitySelector\IblockSectionListProvider;
use PHPUnit\Framework\TestCase;

final class EntitySelectIblockSectionProviderTest extends TestCase
{
    public function testItResolvesIblockSectionListProvider(): void
    {
        $field = EntitySelect::make('Section', 'SECTION_ID')
            ->entityId('iblock-section-list', ['iblockId' => 5]);

        $method = new \ReflectionMethod(EntitySelect::class, 'resolveProviderClass');
        $method->setAccessible(true);

        self::assertSame(IblockSectionListProvider::class, $method->invoke($field));
    }
}
