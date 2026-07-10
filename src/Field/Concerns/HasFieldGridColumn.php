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
    protected ?int $columnWidth = null;
    protected ?string $columnAlign = null;
    protected ?string $columnColor = null;
    protected bool $columnSticked = false;

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

    /** Ширина колонки в пикселях. */
    public function width(int $width): static
    {
        $this->columnWidth = $width;

        return $this;
    }

    /** Заданная ширина колонки в пикселях (или null). */
    public function getColumnWidth(): ?int
    {
        return $this->columnWidth;
    }

    /** Горизонтальное выравнивание содержимого: left|center|right. */
    public function align(string $align): static
    {
        $align = strtolower($align);
        if (!in_array($align, ['left', 'center', 'right'], true)) {
            throw new \InvalidArgumentException('Column align must be one of: left, center, right.');
        }
        $this->columnAlign = $align;

        return $this;
    }

    /** Цвет колонки (CSS-цвет, поддерживаемый main.ui.grid). */
    public function color(?string $color): static
    {
        $this->columnColor = $color;

        return $this;
    }

    /** Закрепить колонку (sticky) при горизонтальной прокрутке. */
    public function sticked(bool $sticked = true): static
    {
        $this->columnSticked = $sticked;

        return $this;
    }

    public function getGridColumnType(): string
    {
        return 'text';
    }

    /** @return array<string,mixed> */
    public function getGridColumnConfig(): array
    {
        $config = [
            'id' => AdminString::safeKey($this->column),
            'name' => $this->label,
            'sort' => (!$this->isComputed() && $this->sortable) ? $this->column : false,
            'default' => true,
            'type' => $this->getGridColumnType(),
            'editable' => $this->isInlineEditable(),
        ];

        if ($this->columnWidth !== null) {
            $config['width'] = $this->columnWidth;
        }
        if ($this->columnAlign !== null) {
            $config['align'] = $this->columnAlign;
        }
        if ($this->columnColor !== null && $this->columnColor !== '') {
            $config['color'] = $this->columnColor;
        }
        if ($this->columnSticked) {
            $config['sticked'] = true;
        }

        return $config;
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
