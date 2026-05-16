<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Resource;

use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;

interface ResourceFieldsContract
{
    /** @return iterable<FieldContract> */
    public function indexFields(): iterable;

    /** @return iterable<FieldContract> */
    public function formFields(): iterable;

    /** @return iterable<FieldContract> */
    public function detailFields(): iterable;

    /** @return iterable<Tab> */
    public function formTabs(): iterable;
}
