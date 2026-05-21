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
        self::assertSame('Processed: 3. Success: 1. Skipped: 1. Failed: 1.', $result->message());
    }

    public function testToArrayIncludesAllDetails(): void
    {
        $result = new BulkResult();
        $result->addSuccess(1);
        $result->addError(2, ['Error 1']);
        $result->addSkipped(3, 'Reason');

        $array = $result->toArray();

        self::assertFalse($array['success']);
        self::assertStringContainsString('Success: 1', $array['message']);
        self::assertSame(3, $array['summary']['total']);
        self::assertSame(['2' => ['Error 1']], $array['errors']);
        self::assertSame(['3' => 'Reason'], $array['skipped']);
        self::assertSame([1], $array['successfulIds']);
    }

    public function testSuccessAndFailureFactories(): void
    {
        $success = BulkResult::success([10, 20]);
        self::assertTrue($success->isSuccess());
        self::assertSame(2, $success->successCount);
        self::assertSame([10, 20], $success->successfulIds);

        $failure = BulkResult::failure('Some global error');
        self::assertFalse($failure->isSuccess());
        self::assertSame(1, $failure->failedCount);
        self::assertSame(['_bulk' => ['Some global error']], $failure->errors());
    }

    public function testMergeErrorsForSameId(): void
    {
        $result = new BulkResult();
        $result->addError(5, 'First error');
        $result->addError(5, 'Second error');

        self::assertSame(['5' => ['First error', 'Second error']], $result->errors());
    }
}
