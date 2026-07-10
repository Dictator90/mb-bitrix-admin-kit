<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Entity;

final class EntitySelectorConfig
{
    /** @param array<int,array<string,mixed>> $entities */
    public function __construct(
        public readonly string $column,
        public readonly string $entityId,
        public readonly array $entityOptions = [],
        public readonly array $entities = [],
        public readonly bool $multiple = false,
        public readonly bool $readonly = false,
        public readonly ?string $placeholder = null,
        public readonly bool $sortable = false,
    ) {
    }
}
