<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Json;
use MB\Bitrix\AdminKit\Field\Layout\Row;
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

    public function testRowGroupTriggersVerticalLayoutAndHorizontalLine(): void
    {
        $html = $this->mixed()->renderFormField([['icon' => 'i', 'title' => 't', 'description' => 'd']]);

        self::assertStringContainsString('class="adminkit-json-row adminkit-json-row--stack"', $html);
        self::assertStringContainsString('class="adminkit-json-line"', $html);
        self::assertStringNotContainsString('class="adminkit-json-header"', $html);
    }

    public function testRowGroupUsesFieldWidthsForTracks(): void
    {
        $html = $this->mixed()->renderFormField([['icon' => 'i', 'title' => 't', 'description' => 'd']]);

        self::assertStringContainsString('grid-template-columns: 80px minmax(0, 1fr);', $html);
    }

    public function testRowChildrenKeepPositionalPostNames(): void
    {
        $html = $this->mixed()->renderFormField([['icon' => 'i', 'title' => 't', 'description' => 'd']]);

        self::assertStringContainsString('name="home[0][icon]"', $html);
        self::assertStringContainsString('name="home[0][title]"', $html);
        self::assertStringContainsString('name="home[0][description]"', $html);
    }

    public function testGetSchemaFlattensRowGroups(): void
    {
        $columns = array_map(
            static fn ($field): string => $field->getColumn(),
            $this->mixed()->getSchema(),
        );

        self::assertSame(['icon', 'title', 'description'], $columns);
    }

    public function testNormalizeIncludesRowChildren(): void
    {
        $result = $this->mixed()->normalize([
            ['icon' => 'star', 'title' => 'Hi', 'description' => 'Desc'],
        ]);

        self::assertSame([
            ['icon' => 'star', 'title' => 'Hi', 'description' => 'Desc'],
        ], $result);
    }

    public function testSingleObjectWithRowGroupKeepsObjectPostNames(): void
    {
        $html = $this->mixed()->multiple(false)->renderFormField(['icon' => 'i', 'title' => 't']);

        self::assertStringContainsString('class="adminkit-json-line"', $html);
        self::assertStringContainsString('name="home[icon]"', $html);
        self::assertStringContainsString('name="home[title]"', $html);
    }

    private function field(): Json
    {
        return Json::make('Блоки', 'home')->fields([
            Text::make('Заголовок', 'title'),
        ]);
    }

    private function mixed(): Json
    {
        return Json::make('Блоки', 'home')->fields([
            Row::make([
                Text::make('Иконка', 'icon')->width(80),
                Text::make('Заголовок', 'title'),
            ]),
            Text::make('Описание', 'description'),
        ]);
    }
}
