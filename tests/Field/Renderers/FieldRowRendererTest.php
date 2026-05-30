<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field\Renderers;

use MB\Bitrix\AdminKit\Field\Renderers\FieldRowContext;
use MB\Bitrix\AdminKit\Field\Renderers\FieldRowRenderer;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use PHPUnit\Framework\TestCase;

final class FieldRowRendererTest extends TestCase
{
    public function testRendersBitrixFormRowAndMetadata(): void
    {
        $field = Text::make('Name', 'NAME')->required()->hint('hint')->visibleWhen('TYPE', 'A');
        $item = DataWrapper::fromArray(['TYPE' => 'B', 'NAME' => 'John']);

        $html = (new FieldRowRenderer())->render(new FieldRowContext(
            field: $field,
            value: 'John',
            item: $item,
            pageType: PageType::FORM,
        ));

        self::assertStringContainsString('ui-form-row', $html);
        self::assertStringContainsString('ui-form-label', $html);
        self::assertStringContainsString('ui-ctl-required', $html);
        self::assertStringContainsString('data-field-column="NAME"', $html);
        self::assertStringContainsString('data-visible-when=', $html);
        self::assertStringContainsString('adminkit-conditional-hidden', $html);
        self::assertStringContainsString('ui-form-content', $html);
    }
}
