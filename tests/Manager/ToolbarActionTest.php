<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Manager;

use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Icon;
use Bitrix\UI\Buttons\Size;
use Bitrix\UI\Toolbar\ButtonLocation;
use MB\Bitrix\AdminKit\Manager\ToolbarAction;
use PHPUnit\Framework\TestCase;

final class ToolbarActionTest extends TestCase
{
    public function testButtonOptionGetters(): void
    {
        $action = ToolbarAction::make('Создать', 'create')
            ->color(Color::SUCCESS)
            ->icon(Icon::ADD)
            ->counter(3)
            ->size(Size::SMALL)
            ->disabled()
            ->round()
            ->collapsedIcon(Icon::ADD)
            ->location(ButtonLocation::RIGHT);

        self::assertSame('create', $action->getId());
        self::assertSame('Создать', $action->getLabel());
        self::assertSame(Color::SUCCESS, $action->getColor());
        self::assertSame(Icon::ADD, $action->getIcon());
        self::assertSame(3, $action->getCounter());
        self::assertSame(Size::SMALL, $action->getSize());
        self::assertTrue($action->isDisabled());
        self::assertTrue($action->isRound());
        self::assertSame(Icon::ADD, $action->getCollapsedIcon());
        self::assertSame(ButtonLocation::RIGHT, $action->getLocation());
    }

    public function testMenuSplitAndSidePanel(): void
    {
        $child = ToolbarAction::make('Sub')->url('/sub')->onclick('alert(1)');
        $action = ToolbarAction::make('Root')
            ->split()
            ->sidePanel(720, 'grid1')
            ->items([$child]);

        self::assertTrue($action->hasMenu());
        self::assertTrue($action->isSplit());
        self::assertSame(['width' => 720, 'gridId' => 'grid1'], $action->getSidePanel());
        self::assertCount(1, $action->getItems());
        self::assertSame('/sub', $action->getItems()[0]->getUrl());
        self::assertSame('alert(1)', $action->getItems()[0]->getOnclick());
    }

    public function testDefaults(): void
    {
        $action = ToolbarAction::make('X');

        self::assertNull($action->getColor());
        self::assertNull($action->getIcon());
        self::assertNull($action->getCounter());
        self::assertNull($action->getSize());
        self::assertNull($action->getCollapsedIcon());
        self::assertFalse($action->isDisabled());
        self::assertFalse($action->isRound());
        self::assertFalse($action->isSplit());
        self::assertFalse($action->hasMenu());
        self::assertNull($action->getSidePanel());
        self::assertSame(ButtonLocation::AFTER_TITLE, $action->getLocation());
        self::assertSame('#', $action->getUrl());
    }
}
