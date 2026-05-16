<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Options;

use Closure;

final class OptionsResolverFactory
{
    public function make(array|Closure|OptionsResolverContract $options, int $cacheTtl = 0): OptionsResolverContract
    {
        $resolver = match (true) {
            $options instanceof OptionsResolverContract => $options,
            $options instanceof Closure => new CallbackOptionsResolver($options),
            default => new ArrayOptionsResolver($options),
        };

        if ($cacheTtl > 0) {
            $resolver = new CachedOptionsResolver($resolver, $cacheTtl);
        }

        return $resolver;
    }
}
