<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Options;

use MB\Bitrix\AdminKit\Database\Performance\ArrayTtlCache;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Support\AdminString;

final class CachedOptionsResolver implements OptionsResolverContract
{
    public function __construct(
        private readonly OptionsResolverContract $inner,
        private readonly int $ttl,
    ) {
    }

    public function resolve(array $context, Select $field): array
    {
        $key = AdminString::cacheKey('adminkit_select_options', [
            'field' => $field::class,
            'column' => $field->getColumn(),
            'context' => $context,
        ]);
        $cached = ArrayTtlCache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $options = $this->inner->resolve($context, $field);
        ArrayTtlCache::set($key, $options, $this->ttl);

        return $options;
    }
}
