<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use MB\Bitrix\AdminKit\Action\RowAction;
use PHPUnit\Framework\TestCase;

final class RowActionTest extends TestCase
{
    public function testEditFactorySetsUrlAndSidePanel(): void
    {
        $action = RowAction::edit('/admin/product/edit.php?ID=1');

        self::assertSame('edit', $action->getId());
        self::assertSame('/admin/product/edit.php?ID=1', $action->getUrl());
        self::assertTrue($action->isUseSidePanel());
    }

    public function testDeleteFactoryEnablesConfirmation(): void
    {
        $action = RowAction::delete();

        self::assertSame('delete', $action->getId());
        self::assertTrue($action->isUseConfirm());
        self::assertNotSame('', $action->getConfirmText());
    }

    public function testViewFactorySetsSidePanel(): void
    {
        $action = RowAction::view('/admin/product/view.php?ID=2');

        self::assertSame('view', $action->getId());
        self::assertTrue($action->isUseSidePanel());
    }
}
