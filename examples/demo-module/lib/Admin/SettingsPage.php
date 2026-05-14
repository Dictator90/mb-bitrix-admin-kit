<?php

declare(strict_types=1);

namespace Vendor\Demo\Admin;

use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Switcher;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Pages\OptionsPage;

final class SettingsPage extends OptionsPage
{
    protected string $moduleId = 'vendor.demo';

    public static function getId(): string
    {
        return 'demo_settings';
    }

    public static function getTitle(): string
    {
        return 'Demo settings';
    }

    public static function getSort(): int
    {
        return 300;
    }

    protected function fields(): iterable
    {
        return [
            Switcher::make('Enable demo integration', 'enabled')->values('Y', 'N')->default('Y'),
            Text::make('API token', 'api_token')->private()->placeholder('Paste token'),
            Select::make('Default product type', 'default_type')->options([
                'simple' => 'Simple product',
                'service' => 'Service',
                'digital' => 'Digital product',
            ])->default('simple'),
        ];
    }
}
