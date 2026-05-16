<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Entity;

final class EntitySelectorSelectedItem
{
    public function __construct(
        public readonly string $entityId,
        public readonly string $id,
        public readonly string $title,
    ) {
    }

    /** @return array<string,string> */
    public function toArray(): array
    {
        return [
            'entityId' => $this->entityId,
            'id' => $this->id,
            'title' => $this->title,
        ];
    }
}
