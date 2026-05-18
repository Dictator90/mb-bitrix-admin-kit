<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Action;

use MB\Bitrix\AdminKit\Action\BulkAction;
use MB\Bitrix\AdminKit\Action\BulkActionDropdown;
use MB\Bitrix\AdminKit\Bitrix\Grid\BitrixGridActionPanelAdapter;
use MB\Bitrix\AdminKit\Grid\Grid;
use PHPUnit\Framework\TestCase;

final class BulkActionDropdownPlaceholderTest extends TestCase
{
    public function testPlaceholderDefaultsToDropdownLabel(): void
    {
        $dropdown = BulkActionDropdown::make('activity', 'Активность');

        self::assertTrue($dropdown->shouldShowPlaceholder());
        self::assertSame('Активность', $dropdown->getPlaceholder());
        self::assertSame('', $dropdown->getPlaceholderValue());
    }

    public function testPlaceholderCanBeOverriddenAndDisabled(): void
    {
        $dropdown = BulkActionDropdown::make('activity', 'Активность')
            ->placeholder('Выберите действие', 'default');

        self::assertSame('Выберите действие', $dropdown->getPlaceholder());
        self::assertSame('default', $dropdown->getPlaceholderValue());

        $dropdown->placeholder(null);
        self::assertFalse($dropdown->shouldShowPlaceholder());

        $dropdown = BulkActionDropdown::make('activity', 'Активность')->withoutPlaceholder();
        self::assertFalse($dropdown->shouldShowPlaceholder());
    }

    public function testRenderedDropdownHasPlaceholderAsFirstItem(): void
    {
        $grid = new Grid('products');
        $grid->setBulkActions([
            BulkActionDropdown::make('activity', 'Активность')
                ->placeholder('Выберите действие')
                ->items([
                    BulkAction::make('activate', 'Активировать'),
                    BulkAction::make('deactivate', 'Деактивировать'),
                ]),
        ]);

        $panel = (new BitrixGridActionPanelAdapter())->componentParams($grid);
        $dropdown = $panel['GROUPS'][0]['ITEMS'][0];

        self::assertArrayNotHasKey('TEXT', $dropdown);
        self::assertSame('Выберите действие', $dropdown['ITEMS'][0]['NAME']);
        self::assertSame('', $dropdown['ITEMS'][0]['VALUE']);
        self::assertArrayNotHasKey('ONCHANGE', $dropdown['ITEMS'][0]);
        self::assertSame('activate', $dropdown['ITEMS'][1]['VALUE']);
        self::assertSame('deactivate', $dropdown['ITEMS'][2]['VALUE']);
    }
}
