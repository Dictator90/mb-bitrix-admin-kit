<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Pages\CustomPage;
use MB\Bitrix\AdminKit\Pages\DashboardPage;
use MB\Bitrix\AdminKit\Pages\OptionsPage;
use MB\Bitrix\AdminKit\Security\PermissionContext;
use PHPUnit\Framework\TestCase;

final class PageV080Test extends TestCase
{
    public function testPageApiKeepsStaticApiAndAddsInstanceApi(): void
    {
        $page = new TestCustomPage();

        self::assertSame('custom_stats', $page->id());
        self::assertSame('Stats', $page->title());
        self::assertSame(20, $page->sort());
        self::assertSame('adm-menu-stat', $page->icon());
        self::assertSame('reports', $page->group());
        self::assertTrue($page->canView(new PermissionContext()));
        self::assertStringContainsString('page=custom_stats', $page->url(['foo' => 'bar']));
        self::assertStringContainsString('foo=bar', $page->url(['foo' => 'bar']));
    }

    public function testCustomPageRendersInsideAdminKitLayout(): void
    {
        $html = (new TestCustomPage())->render();

        self::assertStringContainsString('adminkit-page--custom', $html);
        self::assertStringContainsString('<strong>ok</strong>', $html);
    }

    public function testDashboardPageWrapsWidgets(): void
    {
        $html = (new TestDashboardPage())->render();

        self::assertStringContainsString('adminkit-dashboard', $html);
        self::assertStringContainsString('Widget A', $html);
    }

    public function testOptionsPageExposesFieldsAsComponents(): void
    {
        $page = new TestOptionsPage();

        self::assertCount(1, iterator_to_array($page->fields()));
    }
}

final class TestCustomPage extends CustomPage
{
    public static function getId(): string
    {
        return 'custom_stats';
    }
    public static function getTitle(): string
    {
        return 'Stats';
    }
    public static function getSort(): int
    {
        return 20;
    }
    public static function getMenuIcon(): string
    {
        return 'adm-menu-stat';
    }
    public static function getParentMenuId(): ?string
    {
        return 'reports';
    }
    protected function content(): string
    {
        return '<strong>ok</strong>';
    }
}

final class TestDashboardPage extends DashboardPage
{
    public static function getId(): string
    {
        return 'dashboard';
    }
    public static function getTitle(): string
    {
        return 'Dashboard';
    }
    protected function widgets(): iterable
    {
        return ['Widget A'];
    }
}

final class TestOptionsPage extends OptionsPage
{
    protected string $moduleId = 'test.module';
    public static function getId(): string
    {
        return 'options';
    }
    public static function getTitle(): string
    {
        return 'Options';
    }
    public function fields(): iterable
    {
        return [Text::make('API token', 'api_token')->required()];
    }
}
