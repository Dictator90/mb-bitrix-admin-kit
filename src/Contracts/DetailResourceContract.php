<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

interface DetailResourceContract
{
    /** @return iterable<FieldContract> */
    public function detailFields(): iterable;

    /**
     * @return array<string,mixed>|null
     */
    public function findItem(mixed $id): ?array;
}
