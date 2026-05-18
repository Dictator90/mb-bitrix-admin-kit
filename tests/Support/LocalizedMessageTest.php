<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Support;

use MB\Bitrix\AdminKit\Support\LocalizedMessage;
use PHPUnit\Framework\TestCase;

final class LocalizedMessageTest extends TestCase
{
    public function testItReturnsFallbackWhenLocIsUnavailable(): void
    {
        self::assertSame(
            'Fallback text',
            LocalizedMessage::get(__DIR__ . '/LocalizedMessageTest.php', 'MB_TEST_KEY', 'Fallback text'),
        );
    }

    public function testItAppliesReplacePlaceholdersInFallback(): void
    {
        self::assertSame(
            'Minimum length: 5 characters.',
            LocalizedMessage::get(
                __DIR__ . '/LocalizedMessageTest.php',
                'MB_TEST_MIN',
                'Minimum length: #MIN# characters.',
                ['#MIN#' => '5'],
            ),
        );
    }
}
