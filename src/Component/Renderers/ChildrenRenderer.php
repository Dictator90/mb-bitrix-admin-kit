<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Renderers;

use MB\Bitrix\AdminKit\Component\ComponentContext;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\UI\ItemAwareContract;
use MB\Bitrix\AdminKit\Contracts\UI\PageTypeAwareContract;
use MB\Bitrix\AdminKit\Field\Renderers\FieldRowContext;
use MB\Bitrix\AdminKit\Field\Renderers\FieldRowRenderer;

final class ChildrenRenderer
{
    public function render(iterable $children, ComponentContext $context): string
    {
        $html = '';
        $rowRenderer = new FieldRowRenderer();
        $visibility = new VisibilityWrapper();

        foreach ($children as $child) {
            if ($child instanceof FieldContract) {
                if (!$child->isVisibleOn($context->pageType)) {
                    continue;
                }

                $value = $context->item?->get($child->getColumn()) ?? $child->getDefault();
                $html .= $rowRenderer->render(new FieldRowContext(
                    field: $child,
                    value: $value,
                    item: $context->item,
                    pageType: $context->pageType,
                ));
                continue;
            }

            if (!$child instanceof ComponentContract) {
                continue;
            }

            if ($child instanceof ItemAwareContract) {
                $child = $child->withItem($context->item);
            }
            if ($child instanceof PageTypeAwareContract) {
                $child = $child->withPageType($context->pageType);
            }

            $html .= $visibility->wrap($child->render(), $child, $context);
        }

        return $html;
    }
}
