<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Concerns;

trait HasHtmlAttributes
{
    /** @var list<string> */
    protected array $classes = [];

    /** @var array<string,string> */
    protected array $styles = [];

    /** @var array<string,string> */
    protected array $attrs = [];

    public function class(string ...$classes): static
    {
        $this->classes = array_merge($this->classes, $classes);

        return $this;
    }

    public function style(string $property, string $value): static
    {
        $this->styles[$property] = $value;

        return $this;
    }

    public function attr(string $name, string $value): static
    {
        $this->attrs[$name] = $value;

        return $this;
    }

    protected function buildClassAttr(array $extra = []): string
    {
        $all = array_merge($this->classes, $extra);
        if ($all === []) {
            return '';
        }

        return ' class="' . htmlspecialcharsbx(implode(' ', array_unique($all))) . '"';
    }

    protected function buildStyleAttr(array $extra = []): string
    {
        $all = array_merge($this->styles, $extra);
        if ($all === []) {
            return '';
        }

        $css = '';
        foreach ($all as $prop => $value) {
            $css .= $prop . ':' . $value . ';';
        }

        return ' style="' . htmlspecialcharsbx($css) . '"';
    }

    protected function buildExtraAttrs(): string
    {
        $html = '';
        foreach ($this->attrs as $name => $value) {
            $html .= ' ' . htmlspecialcharsbx($name) . '="' . htmlspecialcharsbx($value) . '"';
        }

        return $html;
    }
}
