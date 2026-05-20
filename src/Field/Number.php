<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

class Number extends Field
{
    protected ?float $min = null;
    protected ?float $max = null;
    protected ?float $step = null;

    public function min(float|int $min, string $message = ''): static
    {
        $this->min = (float)$min;

        return parent::min($min, $message);
    }

    public function max(float|int $max, string $message = ''): static
    {
        $this->max = (float)$max;

        return parent::max($max, $message);
    }

    public function step(float $step): static
    {
        $this->step = $step;

        return $this;
    }

    public function getGridColumnType(): string
    {
        return 'number';
    }

    /** @param array<string,mixed> $formData */
    public function renderFormField(mixed $value = null, array $formData = []): string
    {
        $val = htmlspecialcharsbx((string)$this->resolveValue($value));
        $name = htmlspecialcharsbx($this->column);
        $attrs = '';
        if ($this->min !== null) {
            $attrs .= ' min="' . $this->min . '"';
        }
        if ($this->max !== null) {
            $attrs .= ' max="' . $this->max . '"';
        }
        if ($this->step !== null) {
            $attrs .= ' step="' . $this->step . '"';
        }
        if ($this->required) {
            $attrs .= ' required';
        }
        if ($this->isReadOnlyFor($formData)) {
            $attrs .= ' readonly disabled';
        }
        if ($this->placeholder !== null) {
            $attrs .= ' placeholder="' . htmlspecialcharsbx($this->placeholder) . '"';
        }
        $attrs .= $this->renderReactiveAttrs();

        return <<<HTML
        <div class="ui-ctl ui-ctl-textbox">
            <input type="number" class="ui-ctl-element" name="{$name}" value="{$val}"{$attrs}>
        </div>
        HTML;
    }

    public function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            $first = reset($value);
            $value = $first === false ? null : $first;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (str_contains((string)$value, '.') ? (float)$value : (int)$value) : $value;
    }
}
