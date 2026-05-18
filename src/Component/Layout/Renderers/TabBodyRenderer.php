<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout\Renderers;

use MB\Bitrix\AdminKit\Component\ComponentContext;
use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Renderers\ChildrenRenderer;

final class TabBodyRenderer
{
    public function render(Tab $tab, ComponentContext $context): string
    {
        return '<div class="ui-form">' . (new ChildrenRenderer())->render($tab->getItems(), $context) . '</div>';
    }
}
