<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\MassDeleteAction;
use PHPUnit\Framework\TestCase;

final class BulkActionDeleteTest extends TestCase
{
    public function testDeleteFactoryReturnsMassDeleteActionWithUiDefaults(): void
    {
        $action = BulkAction::delete();

        self::assertInstanceOf(MassDeleteAction::class, $action);
        self::assertTrue($action->needsConfirm());
        self::assertTrue($action->isDanger());
        self::assertSame('danger', $action->getGroup());
        self::assertSame('Deletion', $action->getGroupLabel());
        self::assertSame(900, $action->getGroupSort());
        self::assertSame('ui-btn-icon-remove', $action->getIcon());
        self::assertSame(100, $action->getSort());
    }
}
