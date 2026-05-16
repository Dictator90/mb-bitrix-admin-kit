<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Entity;

final class EntitySelectorEntity
{
    /** @param array<string,mixed> $options */
    public function __construct(
        public readonly string $id,
        public readonly array $options = [],
        public readonly bool $dynamicLoad = true,
        public readonly bool $dynamicSearch = true,
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'options' => $this->options,
            'dynamicLoad' => $this->dynamicLoad,
            'dynamicSearch' => $this->dynamicSearch,
        ];
    }
}
