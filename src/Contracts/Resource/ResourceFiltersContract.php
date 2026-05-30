<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

use MB\Bitrix\AdminKit\Contracts\FilterContract;

interface ResourceFiltersContract
{
    /** @return iterable<FilterContract> */
    public function filters(): iterable;

    /**
     * Колонки для быстрого поиска из тулбара (поле FIND).
     * Пустой массив — взять строковые поля фильтра.
     *
     * @return array<int,string>
     */
    public function searchColumns(): array;
}
