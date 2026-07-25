<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Json;
use MB\Bitrix\AdminKit\Field\Text;
use PHPUnit\Framework\TestCase;

final class JsonLayoutTest extends TestCase
{
    public function testLayoutTogglesReportState(): void
    {
        self::assertFalse(Json::make('J', 'j')->isStacked());
        self::assertTrue(Json::make('J', 'j')->stacked()->isStacked());
        self::assertTrue(Json::make('J', 'j')->vertical()->isStacked());
        self::assertTrue(Json::make('J', 'j')->layout(Json::LAYOUT_STACK)->isStacked());
        self::assertFalse(Json::make('J', 'j')->stacked()->stacked(false)->isStacked());
        self::assertFalse(Json::make('J', 'j')->layout('unknown')->isStacked());
    }

    public function testDefaultRowLayoutRendersSharedHeaderAndNoStackClass(): void
    {
        $html = $this->field()->renderFormField([['title' => 'A']]);

        self::assertStringContainsString('class="adminkit-json-header"', $html);
        self::assertStringNotContainsString('class="adminkit-json-row adminkit-json-row--stack"', $html);
    }

    public function testStackedMultipleRendersCardsWithInlineLabelsAndNoHeader(): void
    {
        $html = $this->field()->stacked()->renderFormField([['title' => 'A']]);

        self::assertStringContainsString('class="adminkit-json-row adminkit-json-row--stack"', $html);
        self::assertStringContainsString('class="adminkit-json-cell-label"', $html);
        self::assertStringContainsString('Заголовок', $html);
        self::assertStringNotContainsString('class="adminkit-json-header"', $html);
    }

    public function testStackedSingleRendersVerticallyWithoutHeader(): void
    {
        $html = $this->field()->multiple(false)->stacked()->renderFormField(['title' => 'A']);

        self::assertStringContainsString('class="adminkit-json-row adminkit-json-row--stack"', $html);
        self::assertStringContainsString('class="adminkit-json-cell-label"', $html);
        self::assertStringNotContainsString('class="adminkit-json-header"', $html);
    }

    public function testStackedRowsKeepPositionalPostNameContract(): void
    {
        $html = $this->field()->stacked()->renderFormField([['title' => 'A']]);

        self::assertStringContainsString('name="home[0][title]"', $html);
    }

    public function testStackedSingleKeepsObjectPostNameContract(): void
    {
        $html = $this->field()->multiple(false)->stacked()->renderFormField(['title' => 'A']);

        self::assertStringContainsString('name="home[title]"', $html);
    }

    private function field(): Json
    {
        return Json::make('Блоки', 'home')->fields([
            Text::make('Заголовок', 'title'),
        ]);
    }
}
