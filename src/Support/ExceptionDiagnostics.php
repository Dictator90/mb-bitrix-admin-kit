<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support;

use Throwable;

final class ExceptionDiagnostics
{
    /**
     * @return list<string> Message, location, and stack trace as separate global error lines.
     */
    public static function toGlobalErrors(Throwable $exception, ?string $fallback = null): array
    {
        $message = trim($exception->getMessage());
        if ($message === '') {
            $message = $fallback ?? $exception::class;
        }

        return [
            $message,
            sprintf('[%s] %s:%d', $exception::class, $exception->getFile(), $exception->getLine()),
            $exception->getTraceAsString(),
        ];
    }

    public static function format(Throwable $exception, ?string $fallback = null): string
    {
        return implode("\n\n", self::toGlobalErrors($exception, $fallback));
    }
}
