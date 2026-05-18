<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Options;

use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class ArrayOptionsResolver implements OptionsResolverContract
{
    /** @param array<mixed> $options */
    public function __construct(private readonly array $options)
    {
    }

    public function resolve(array $context, Select $field): array
    {
        unset($context, $field);

        return AdminCollection::make($this->options)->all();
    }
}
