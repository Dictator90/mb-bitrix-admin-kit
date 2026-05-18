<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Database;

use Bitrix\Main\Localization\Loc;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class BulkResult
{
    public int $total = 0;
    public int $successCount = 0;
    public int $failedCount = 0;

    /** @var array<string,string[]> */
    public array $errorsById = [];

    /** @var array<string,string> */
    public array $skippedIds = [];

    /** @var array<int,mixed> */
    public array $successfulIds = [];

    public static function success(array $ids = []): self
    {
        $result = new self();
        foreach (AdminCollection::make($ids)->all() as $id) {
            $result->addSuccess($id);
        }

        return $result;
    }

    public static function failure(string|array $errors, mixed $id = '_bulk'): self
    {
        $result = new self();
        $result->addError($id, $errors);

        return $result;
    }

    public function addSuccess(mixed $id): void
    {
        $this->successfulIds[] = $id;
        $this->successCount++;
        $this->recalculateTotal();
    }

    public function addError(mixed $id, string|array $errors): void
    {
        $key = $this->key($id);
        $messages = array_values(array_filter(array_map('strval', (array)$errors), static fn (string $error): bool => $error !== ''));
        $this->errorsById[$key] = array_merge(
            $this->errorsById[$key] ?? [],
            $messages ?: [$this->translate('MB_ADMIN_KIT_BULK_RESULT_FAILED', 'Bulk operation failed.')],
        );
        $this->failedCount = count($this->errorsById);
        $this->recalculateTotal();
    }

    public function addSkipped(mixed $id, string $reason): void
    {
        $this->skippedIds[$this->key($id)] = $reason;
        $this->recalculateTotal();
    }

    public function isSuccess(): bool
    {
        return $this->failedCount === 0;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'success' => $this->isSuccess(),
            'status' => $this->isSuccess() ? 'success' : 'error',
            'message' => $this->message(),
            'summary' => $this->summary(),
            'errors' => $this->errorsById,
            'warnings' => $this->skippedIds,
            'skipped' => $this->skippedIds,
            'affected' => $this->successCount,
            'successfulIds' => $this->successfulIds,
        ];
    }

    /** @return array<string,string[]> */
    public function errors(): array
    {
        return $this->errorsById;
    }

    /** @return array<string,int> */
    public function summary(): array
    {
        return [
            'total' => $this->total,
            'success' => $this->successCount,
            'skipped' => count($this->skippedIds),
            'failed' => $this->failedCount,
        ];
    }

    public function message(): string
    {
        $summary = $this->summary();
        $template = $this->translate(
            'MB_ADMIN_KIT_BULK_RESULT_SUMMARY',
            'Processed: #TOTAL#. Success: #SUCCESS#. Skipped: #SKIPPED#. Failed: #FAILED#.',
        );

        return str_replace(
            ['#TOTAL#', '#SUCCESS#', '#SKIPPED#', '#FAILED#'],
            [(string)$summary['total'], (string)$summary['success'], (string)$summary['skipped'], (string)$summary['failed']],
            $template,
        );
    }

    private function recalculateTotal(): void
    {
        $this->total = $this->successCount + $this->failedCount + count($this->skippedIds);
    }

    private function key(mixed $id): string
    {
        return is_scalar($id) || $id === null ? (string)$id : md5(serialize($id));
    }

    private function translate(string $key, string $fallback): string
    {
        if (class_exists(Loc::class)) {
            Loc::loadMessages(__FILE__);

            return (string)(Loc::getMessage($key) ?: $fallback);
        }

        return $fallback;
    }
}
