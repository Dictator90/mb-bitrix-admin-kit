<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

trait HasResourceFilters
{
    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\FilterContract> */
    public function filters(): iterable
    {
        return [];
    }

    /**
     * Колонки для быстрого поиска из тулбара (поле FIND).
     * Пустой массив — взять строковые поля фильтра.
     *
     * @return array<int,string>
     */
    public function searchColumns(): array
    {
        return [];
    }
}
