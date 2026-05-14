<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Pages\DashboardPage as BaseDashboardPage;
use Vendor\Demo\Orm\ProductTable;

final class DashboardPage extends BaseDashboardPage
{
    public static function getId(): string
    {
        return 'demo_dashboard';
    }

    public static function getTitle(): string
    {
        return 'Demo dashboard';
    }

    public static function getSort(): int
    {
        return 100;
    }

    public static function getMenuIcon(): string
    {
        return 'form_menu_icon';
    }

    protected function widgets(): iterable
    {
        $count = method_exists(ProductTable::class, 'getCount') ? ProductTable::getCount([]) : 0;

        return [
            '<section class="adminkit-demo-widget"><h2 class="adminkit-demo-widget__title">Products</h2><p class="adminkit-demo-widget__value">' . (int)$count . '</p></section>',
            '<section class="adminkit-demo-widget"><h2 class="adminkit-demo-widget__title">DX checklist</h2><ul class="adminkit-demo-widget__list"><li>CRUD</li><li>OptionsPage</li><li>SidePanel</li></ul></section>',
        ];
    }
}
