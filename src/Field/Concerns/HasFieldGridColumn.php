<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

use MB\Bitrix\AdminKit\Grid\Row\FieldAssembler;
use MB\Bitrix\AdminKit\Support\AdminString;

trait HasFieldGridColumn
{
    protected bool $sortable = true;
    protected bool $editable = false;
    protected bool $asEditLink = false;

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function editable(bool $editable = true): static
    {
        $this->editable = $editable;

        return $this;
    }

    public function getGridColumnType(): string
    {
        return 'text';
    }

    /** @return array<string,mixed> */
    public function getGridColumnConfig(): array
    {
        return [
            'id' => AdminString::safeKey($this->column),
            'name' => $this->label,
            'sort' => (!$this->isComputed() && $this->sortable) ? $this->column : false,
            'default' => true,
            'type' => $this->getGridColumnType(),
            'editable' => $this->isInlineEditable(),
        ];
    }


    protected function supportsInlineEdit(): bool
    {
        return true;
    }

    protected function isInlineEditable(): bool
    {
        return $this->editable && !$this->isReadOnly() && $this->supportsInlineEdit();
    }

    public function getFilterType(): ?string
    {
        return null;
    }

    public function asEditLink(bool $enabled = true): static
    {
        $this->asEditLink = $enabled;

        return $this;
    }

    public function linkToEdit(bool $enabled = true): static
    {
        return $this->asEditLink($enabled);
    }

    public function shouldRenderAsEditLink(): bool
    {
        return $this->asEditLink;
    }

    public function getFieldAssembler(): ?FieldAssembler
    {
        return null;
    }
}
