<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout;

/**
 * Vertical spacer.
 *
 * Usage:
 *   LineBreak::make()
 *   LineBreak::make(24)   // 24px height
 */
class LineBreak extends AbstractLayoutComponent
{
    protected int $height;

    public function __construct(int $height = 16)
    {
        parent::__construct([]);
        $this->height = $height;
    }

    public static function make(int $height = 16): static
    {
        return new static($height);
    }

    public function render(): string
    {
        return '<div style="height:' . $this->height . 'px;"></div>';
    }

    /** @return never[] */
    public function extractFields(): array
    {
        return [];
    }
}
