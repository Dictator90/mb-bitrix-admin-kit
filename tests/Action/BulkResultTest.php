<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use MB\Bitrix\AdminKit\Database\BulkResult;
use PHPUnit\Framework\TestCase;

final class BulkResultTest extends TestCase
{
    public function testItTracksSuccessErrorsAndSkippedIds(): void
    {
        $result = new BulkResult();
        $result->addSuccess(1);
        $result->addError(2, ['Broken']);
        $result->addSkipped(3, 'Denied');

        self::assertFalse($result->isSuccess());
        self::assertSame(3, $result->total);
        self::assertSame(1, $result->successCount);
        self::assertSame(1, $result->failedCount);
        self::assertSame(['2' => ['Broken']], $result->errors());
        self::assertSame(['3' => 'Denied'], $result->skippedIds);
        self::assertSame('Обработано: 3. Успешно: 1. Пропущено: 1. С ошибкой: 1.', $result->message());
    }
}
