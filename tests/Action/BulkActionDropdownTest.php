<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;
use MB\Bitrix\AdminKit\Contracts\Action\BulkPanelItemContract;
use PHPUnit\Framework\TestCase;

final class BulkActionDropdownTest extends TestCase
{
    public function testItStoresMetadata(): void
    {
        $dropdown = BulkActionDropdown::make('activity', 'Активность')
            ->group('status', 'Статус')
            ->sort(10)
            ->icon('my-icon')
            ->buttonClass('my-class')
            ->title('My Title')
            ->multiple(true);

        self::assertSame('activity', $dropdown->getId());
        self::assertSame('Активность', $dropdown->getLabel());
        self::assertSame('status', $dropdown->getGroup());
        self::assertSame('Статус', $dropdown->getGroupLabel());
        self::assertSame(10, $dropdown->getSort());
        self::assertSame('my-icon', $dropdown->getIcon());
        self::assertSame('my-class', $dropdown->getButtonClass());
        self::assertSame('My Title', $dropdown->getTitle());
        self::assertTrue($dropdown->isMultiple());
    }

    public function testItSupportsItems(): void
    {
        $dropdown = BulkActionDropdown::make('activity')
            ->item(BulkAction::make('activate', 'Activate'))
            ->items([
                BulkAction::make('deactivate', 'Deactivate'),
            ]);

        $items = $dropdown->getItems();
        self::assertCount(2, $items);
        self::assertSame('activate', $items[0]->getId());
        self::assertSame('deactivate', $items[1]->getId());
    }

    public function testItImplementsContract(): void
    {
        $dropdown = BulkActionDropdown::make('test');
        $action = BulkAction::make('test');

        self::assertInstanceOf(BulkPanelItemContract::class, $dropdown);
        self::assertInstanceOf(BulkPanelItemContract::class, $action);
    }
}
