<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field\Concerns;

trait HasFieldHelp
{
    protected ?string $hint = null;
    protected ?string $help = null;
    protected ?string $placeholder = null;

    public function hint(string $hint): static
    {
        $this->hint = $hint;

        return $this;
    }

    public function help(?string $text): static
    {
        $this->help = $text;
        $this->hint = $text;

        return $this;
    }

    public function placeholder(?string $text): static
    {
        $this->placeholder = $text;

        return $this;
    }

    public function renderHint(): string
    {
        if ($this->hint === null) {
            return '';
        }

        return '<span class="ui-hint" data-hint="' . htmlspecialcharsbx($this->hint) . '"><span class="ui-hint-icon"></span></span>';
    }

    protected function renderRequired(): string
    {
        return $this->required ? '<span class="ui-ctl-required">*</span>' : '';
    }
}
