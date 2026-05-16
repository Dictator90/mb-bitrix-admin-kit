<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;

interface FieldGridColumnContract
{
    public function getGridColumnType(): string;

    /** @return array<string,mixed> */
    public function getGridColumnConfig(): array;

    public function asEditLink(bool $enabled = true): static;

    public function linkToEdit(bool $enabled = true): static;

    public function shouldRenderAsEditLink(): bool;

    public function getFieldAssembler(): ?FieldAssembler;
}
