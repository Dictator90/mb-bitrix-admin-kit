<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Layout\Renderers;

use MB\Bitrix\AdminKit\Component\Layout\Tab;

final class TabHeaderRenderer
{
    public function render(Tab $tab): string
    {
        $id = htmlspecialchars($tab->getId(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $activeClass = $tab->isActive() ? ' --header-active' : '';
        $title = htmlspecialchars($tab->getTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $description = $tab->getDescription();
        $titleAttr = $description !== null
            ? ' title="' . htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"'
            : '';

        $inner = '';
        $icon = $tab->getIcon();
        if ($icon !== null && $icon !== '') {
            $iconClass = htmlspecialchars($icon, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $inner .= '<span class="ui-tabs__tab-header-icon ui-icon-set ' . $iconClass . '"></span>';
        }

        $inner .= '<span class="ui-tabs__tab-header-title"' . $titleAttr . '>' . $title . '</span>';

        $count = $tab->getCount();
        if ($count !== null && $count !== '') {
            $inner .= '<span class="ui-tabs__tab-header-count">'
                . htmlspecialchars((string)$count, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</span>';
        }

        return '<span class="ui-tabs__tab-header-container' . $activeClass
            . '" data-bx-role="tab-header" data-bx-name="' . $id . '">'
            . '<span class="ui-tabs__tab-header-inner">' . $inner . '</span>'
            . '</span>';
    }
}
