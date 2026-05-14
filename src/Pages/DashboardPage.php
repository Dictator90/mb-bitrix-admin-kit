<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Pages;

/** Dashboard page skeleton for module overview pages. */
abstract class DashboardPage extends CustomPage
{
    protected function pageTitle(): string
    {
        return $this->title();
    }

    protected function content(): string
    {
        $widgets = [];
        foreach ($this->widgets() as $widget) {
            $widgets[] = '<div class="adminkit-dashboard__widget">' . (string)$widget . '</div>';
        }

        return '<div class="adminkit-dashboard">' . implode('', $widgets) . '</div>';
    }

    /** @return iterable<string> */
    protected function widgets(): iterable
    {
        return [];
    }
}
