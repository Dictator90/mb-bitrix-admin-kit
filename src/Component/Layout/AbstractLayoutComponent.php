<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout;

use MB\Bitrix\AdminKit\Contracts\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

abstract class AbstractLayoutComponent implements ComponentContract
{

    /** @var array<int, FieldContract|ComponentContract> */
    protected array $children = [];
    protected array $classes = [];
    protected array $styles = [];
    protected array $attrs = [];
    protected ?DataWrapper $item = null;
    protected PageType $pageType = PageType::FORM;

    /** @param array<int, FieldContract|ComponentContract> $children */
    public function __construct(array $children = [])
    {
        $this->children = $children;
    }

    /** @param array<int, FieldContract|ComponentContract> $children */
    public function children(array $children): static
    {
        $this->children = $children;

        return $this;
    }

    /** @param FieldContract|ComponentContract $child */
    public function add(mixed $child): static
    {
        $this->children[] = $child;

        return $this;
    }

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

    public function withItem(?DataWrapper $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function withPageType(PageType $type): static
    {
        $this->pageType = $type;

        return $this;
    }

    protected ?array $visibleWhenRule = null;

    /**
     * Show this component only when another field has the given value.
     */
    public function visibleWhen(string $column, mixed $value): static
    {
        $this->visibleWhenRule = is_array($value)
            ? ['column' => $column, 'values' => array_map('strval', $value)]
            : ['column' => $column, 'value' => (string)$value];

        return $this;
    }

    public function getVisibleWhen(): ?array
    {
        return $this->visibleWhenRule;
    }

    protected function checkVisibilityRule(array $rule, mixed $currentValue): bool
    {
        $str = (string)($currentValue ?? '');
        if (isset($rule['values'])) {
            return in_array($str, $rule['values'], true);
        }
        return $str === ($rule['value'] ?? '');
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /** @return FieldContract[] */
    public function extractFields(): array
    {
        $fields = [];

        foreach ($this->children as $child) {
            if ($child instanceof FieldContract) {
                $fields[] = $child;
            } elseif ($child instanceof ComponentContract) {
                $fields = array_merge($fields, $child->extractFields());
            }
        }

        return $fields;
    }

    protected function renderChildren(): string
    {
        $html = '';

        foreach ($this->children as $child) {
            if ($child instanceof ComponentContract) {
                $inner = $child->withPageType($this->pageType)->withItem($this->item)->render();
                $html .= $this->wrapWithConditionalVisibility($child, $inner);
            } elseif ($child instanceof FieldContract) {
                if ($child->isVisibleOn($this->pageType)) {
                    $html .= $this->renderField($child);
                }
            }
        }

        return $html;
    }

    protected function wrapWithConditionalVisibility(ComponentContract $component, string $inner): string
    {
        if (!method_exists($component, 'getVisibleWhen')) {
            return $inner;
        }
        $rule = $component->getVisibleWhen();
        if ($rule === null) {
            return $inner;
        }

        $json    = htmlspecialcharsbx(json_encode($rule));
        $colVal  = $this->item?->get($rule['column']);
        $hidden  = $this->checkVisibilityRule($rule, $colVal) ? '' : ' adminkit-conditional-hidden';

        return '<div data-visible-when="' . $json . '" class="adminkit-visibility-wrapper' . $hidden . '">' . $inner . '</div>';
    }

    protected function renderField(FieldContract $field): string
    {
        $value        = $this->item?->get($field->getColumn());
        $column       = htmlspecialcharsbx($field->getColumn());
        $label        = htmlspecialcharsbx($field->getLabel());
        $requiredMark = $field->isRequired() ? ' <span class="ui-ctl-required">*</span>' : '';
        $hint         = method_exists($field, 'renderHint') ? $field->renderHint() : '';

        $visibilityAttr = '';
        $extraClass     = '';
        if (method_exists($field, 'getVisibleWhen') && ($rule = $field->getVisibleWhen()) !== null) {
            $visibilityAttr = ' data-visible-when="' . htmlspecialcharsbx(json_encode($rule)) . '"';
            if (!$this->checkVisibilityRule($rule, $this->item?->get($rule['column']))) {
                $extraClass = ' adminkit-conditional-hidden';
            }
        }

        return '<div class="ui-form-row' . $extraClass . '" data-field-column="' . $column . '"' . $visibilityAttr . '>'
            . '<div class="ui-form-label"><div class="ui-ctl-label-text">' . $label . $requiredMark . $hint . '</div></div>'
            . '<div class="ui-form-content">' . $field->renderFormField($value) . '</div>'
            . '</div>';
    }

    protected function buildClassAttr(array $extra = []): string
    {
        $all = array_merge($this->classes, $extra);

        if (empty($all)) {
            return '';
        }

        return ' class="' . htmlspecialcharsbx(implode(' ', array_unique($all))) . '"';
    }

    protected function buildStyleAttr(array $extra = []): string
    {
        $all = array_merge($this->styles, $extra);

        if (empty($all)) {
            return '';
        }

        $css = '';
        foreach ($all as $prop => $val) {
            $css .= $prop . ':' . $val . ';';
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
