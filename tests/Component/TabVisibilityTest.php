<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Component;

use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Layout\Tabs;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Page\Standalone\Services\OptionFieldExtractor;
use PHPUnit\Framework\TestCase;

final class TabVisibilityTest extends TestCase
{
    public function testTabIsVisibleByDefault(): void
    {
        self::assertTrue(Tab::make('Foo')->isVisible());
    }

    public function testCanSeeBoolHidesTab(): void
    {
        self::assertFalse(Tab::make('Foo')->canSee(false)->isVisible());
        self::assertTrue(Tab::make('Foo')->canSee(true)->isVisible());
    }

    public function testCanSeeClosureIsEvaluated(): void
    {
        $tab = Tab::make('Foo')->canSee(static fn (Tab $t): bool => $t->getTitle() === 'Foo');
        self::assertTrue($tab->isVisible());

        $hidden = Tab::make('Foo')->canSee(static fn (): bool => false);
        self::assertFalse($hidden->isVisible());
    }

    public function testVisibleAliasMatchesCanSee(): void
    {
        self::assertFalse(Tab::make('Foo')->visible(false)->isVisible());
    }

    public function testTabsExtractFieldsSkipsHiddenTabs(): void
    {
        $visibleField = Text::make('Visible', 'V');
        $hiddenField = Text::make('Hidden', 'H');

        $tabs = Tabs::make([
            Tab::make('Visible')->fields($visibleField),
            Tab::make('Hidden')->canSee(false)->fields($hiddenField),
        ]);

        $fields = $tabs->extractFields();

        self::assertSame(['V'], array_map(static fn ($f) => $f->getColumn(), $fields));
    }

    public function testTabsRenderReturnsEmptyWhenAllTabsHidden(): void
    {
        $tabs = Tabs::make([
            Tab::make('A')->canSee(false),
            Tab::make('B')->canSee(false),
        ]);

        self::assertSame('', $tabs->render());
    }

    public function testOptionFieldExtractorSkipsHiddenTabs(): void
    {
        $visibleField = Text::make('Visible', 'V');
        $hiddenField = Text::make('Hidden', 'H');

        $components = [
            Tab::make('Visible')->fields($visibleField),
            Tab::make('Hidden')->canSee(false)->fields($hiddenField),
        ];

        $fields = (new OptionFieldExtractor())->extract($components);

        self::assertSame(['V'], array_map(static fn ($f) => $f->getColumn(), $fields));
    }
}
