<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Pages;

use Bitrix\Main\UI\Extension;

/**
 * Base for arbitrary admin pages (dashboards, reports, etc.).
 *
 * Usage:
 *   class DashboardPage extends CustomPage
 *   {
 *       public static function getId(): string    { return 'dashboard'; }
 *       public static function getTitle(): string { return 'Дашборд'; }
 *
 *       protected function content(): string
 *       {
 *           return '<p>Hello from the dashboard!</p>';
 *       }
 *   }
 */
abstract class CustomPage extends AbstractPage
{
    /** @var string[] Bitrix UI extensions to load before rendering. */
    protected array $extensions = [];

    /** Return the HTML content of the page. */
    abstract protected function content(): string;

    public function render(): void
    {
        global $APPLICATION;

        if (!empty($this->extensions)) {
            Extension::load($this->extensions);
        }

        $APPLICATION->SetTitle(static::getTitle());

        echo $this->content();
    }
}
