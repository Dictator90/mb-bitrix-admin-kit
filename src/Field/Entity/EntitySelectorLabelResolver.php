<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Entity;

use Bitrix\UI\EntitySelector\Item;
use Closure;

final class EntitySelectorLabelResolver
{
    /**
     * @param list<string> $ids
     * @param array<string,mixed> $providerOptions
     * @return array<string,string>
     */
    public function resolve(
        array $ids,
        ?Closure $labelResolver,
        ?string $providerClass,
        array $providerOptions = [],
    ): array {
        if ($ids === []) {
            return [];
        }

        if ($labelResolver instanceof Closure) {
            return array_map('strval', ($labelResolver)($ids));
        }

        if ($providerClass === null || !class_exists($providerClass)) {
            return [];
        }

        $providerOptions['selected'] = $ids;
        $provider = new $providerClass($providerOptions);
        if (!method_exists($provider, 'getItems')) {
            return [];
        }

        $result = [];
        foreach ((array)$provider->getItems($ids) as $item) {
            if (!$item instanceof Item) {
                continue;
            }

            $id = (string)$item->getId();
            $title = (string)$item->getTitle();
            if ($id === '' || $title === '') {
                continue;
            }
            $result[$id] = $title;
        }

        return $result;
    }
}
