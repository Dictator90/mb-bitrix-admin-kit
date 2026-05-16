<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Page;

interface OptionsPageContract extends StandalonePageContract
{
    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\Field\FieldContract|\MB\Bitrix\AdminKit\Contracts\UI\ComponentContract> */
    public function fields(): iterable;
}
