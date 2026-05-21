<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Support;

use MB\Bitrix\AdminKit\Support\ExceptionDiagnostics;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExceptionDiagnosticsTest extends TestCase
{
    public function testToGlobalErrorsIncludesMessageLocationAndTrace(): void
    {
        try {
            throw new RuntimeException('Save failed');
        } catch (RuntimeException $exception) {
            $errors = ExceptionDiagnostics::toGlobalErrors($exception);

            self::assertCount(3, $errors);
            self::assertSame('Save failed', $errors[0]);
            self::assertStringContainsString('RuntimeException', $errors[1]);
            self::assertStringContainsString(__FILE__, $errors[1]);
            self::assertStringContainsString('#0 ', $errors[2]);
        }
    }
}
