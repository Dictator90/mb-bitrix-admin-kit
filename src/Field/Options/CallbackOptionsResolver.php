<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Options;

use Closure;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class CallbackOptionsResolver implements OptionsResolverContract
{
    public function __construct(private readonly Closure $callback)
    {
    }

    public function resolve(array $context, Select $field): array
    {
        $options = ($this->callback)($context, $field);

        return AdminCollection::make(is_iterable($options) ? $options : [])->all();
    }
}
