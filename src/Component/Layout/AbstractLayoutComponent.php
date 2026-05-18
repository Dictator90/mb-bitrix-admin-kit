<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout;

use MB\Bitrix\AdminKit\Component\Concerns\ExtractsFields;
use MB\Bitrix\AdminKit\Component\Concerns\HasChildren;
use MB\Bitrix\AdminKit\Component\Concerns\HasComponentContext;
use MB\Bitrix\AdminKit\Component\Concerns\HasConditionalVisibility;
use MB\Bitrix\AdminKit\Component\Concerns\HasHtmlAttributes;
use MB\Bitrix\AdminKit\Component\Renderers\ChildrenRenderer;
use MB\Bitrix\AdminKit\Contracts\UI\LayoutComponentContract;

abstract class AbstractLayoutComponent implements LayoutComponentContract
{
    use HasChildren;
    use HasHtmlAttributes;
    use HasComponentContext;
    use HasConditionalVisibility;
    use ExtractsFields;

    /** @param array<int, \MB\Bitrix\AdminKit\Contracts\Field\FieldContract|\MB\Bitrix\AdminKit\Contracts\UI\ComponentContract> $children */
    public function __construct(array $children = [])
    {
        $this->children = $children;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    protected function renderChildren(): string
    {
        return (new ChildrenRenderer())->render($this->children, $this->context());
    }
}
