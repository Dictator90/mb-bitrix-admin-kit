<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

trait HasResourceFields
{
    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\Field\FieldContract> */
    protected function fields(): iterable
    {
        return [];
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\Field\FieldContract> */
    public function indexFields(): iterable
    {
        return $this->fields();
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\Field\FieldContract> */
    public function formFields(): iterable
    {
        return $this->fields();
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Contracts\Field\FieldContract> */
    public function detailFields(): iterable
    {
        return $this->formFields();
    }

    /** @return iterable<\MB\Bitrix\AdminKit\Component\Layout\Tab> */
    public function formTabs(): iterable
    {
        return [];
    }
}
