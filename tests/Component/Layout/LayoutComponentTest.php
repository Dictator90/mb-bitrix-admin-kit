<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Component\Layout;

use MB\Bitrix\AdminKit\Component\Layout\Box;
use MB\Bitrix\AdminKit\Component\Layout\Column;
use MB\Bitrix\AdminKit\Component\Layout\Grid;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use PHPUnit\Framework\TestCase;

final class LayoutComponentTest extends TestCase
{
    public function testBoxGridAndColumnRenderChildrenViaSharedRenderer(): void
    {
        $field = Text::make('Name', 'NAME');
        $box = Box::make([$field])->withItem(DataWrapper::fromArray(['NAME' => 'A']))->withPageType(PageType::FORM);
        $grid = Grid::make([Column::make([$field])->withItem(DataWrapper::fromArray(['NAME' => 'B']))])->withItem(DataWrapper::fromArray(['NAME' => 'B']));

        self::assertStringContainsString('ui-form-row', $box->render());
        self::assertStringContainsString('ui-form-row', $grid->render());
    }
}
