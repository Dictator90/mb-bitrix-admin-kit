<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use InvalidArgumentException;
use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;
use MB\Bitrix\AdminKit\Grid\Grid;
use PHPUnit\Framework\TestCase;

final class GridBulkActionsValidationTest extends TestCase
{
    public function testSetBulkActionsAcceptsValidItems(): void
    {
        $grid = new Grid('test');
        $actions = [
            BulkAction::make('test', 'Test'),
            BulkActionDropdown::make('drop', 'Drop'),
        ];

        $grid->setBulkActions($actions);

        self::assertCount(2, $grid->getBulkActions());
    }

    public function testSetBulkActionsThrowsExceptionForInvalidItem(): void
    {
        $grid = new Grid('test');
        $actions = [
            BulkAction::make('test', 'Test'),
            new \stdClass(),
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Grid bulk action must implement MB\Bitrix\AdminKit\Contracts\Action\BulkPanelItemContract');

        $grid->setBulkActions($actions);
    }
}
