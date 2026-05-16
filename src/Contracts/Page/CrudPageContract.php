<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Page;

interface CrudPageContract extends ResourcePageContract
{
    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\FieldContract> */
    public function fields(): iterable;

    public function isAsync(): bool;
}
