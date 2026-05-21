<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Component\Layout;

use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Layout\Tabs;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use PHPUnit\Framework\TestCase;

final class TabsRendererTest extends TestCase
{
    public function testTabsRenderUsesDataAttributes(): void
    {
        $tabs = Tabs::make([
            Tab::make('Main', [Text::make('Name', 'NAME')])->active(),
            Tab::make('Extra', [Text::make('Code', 'CODE')]),
        ])->withItem(DataWrapper::fromArray(['NAME' => 'John', 'CODE' => 'A']))->withPageType(PageType::FORM);

        $html = $tabs->render();

        self::assertStringContainsString('data-adminkit-tabs', $html);
        self::assertStringContainsString('data-adminkit-tabs-config', $html);
        self::assertStringContainsString('data-adminkit-tabs-prerendered="Y"', $html);
        self::assertStringContainsString('data-bx-role="tab-header"', $html);
        self::assertStringNotContainsString('mb.ui.tabs', $html);
        self::assertCount(2, $tabs->extractFields());

        // Verify fix for nested tabs is present in inline JS
        self::assertStringContainsString('Array.from(bodiesContainer.children).forEach', $html);
        self::assertStringContainsString('Array.from(headersContainer.children).forEach', $html);
    }
}
