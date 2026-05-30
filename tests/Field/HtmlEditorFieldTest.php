<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\HtmlEditor;
use PHPUnit\Framework\TestCase;

final class HtmlEditorFieldTest extends TestCase
{
    public function testDisableEditorRendersTextarea(): void
    {
        $html = HtmlEditor::make('Content', 'HTML_BODY')
            ->disableEditor()
            ->renderFormField('<p>Hello</p>');

        self::assertStringContainsString('<textarea', $html);
        self::assertStringContainsString('name="HTML_BODY"', $html);
        self::assertStringContainsString('&lt;p&gt;Hello&lt;/p&gt;', $html);
        self::assertStringNotContainsString('bx-html-editor', $html);
    }

    public function testReadonlyRendersTextareaEvenWhenEditorEnabled(): void
    {
        $html = HtmlEditor::make('Content', 'HTML_BODY')
            ->readonly()
            ->renderFormField('<p>Hello</p>');

        self::assertStringContainsString('<textarea', $html);
        self::assertStringContainsString('readonly', $html);
    }

    public function testBuildEditorIdSanitizesInputName(): void
    {
        $field = new HtmlEditorFieldTestDouble('Content', 'HTML_BODY');

        self::assertSame('adminkit_html_KIT_TEST_HTML', $field->exposeEditorId('KIT_TEST_HTML'));
        self::assertSame('adminkit_html_field_name', $field->exposeEditorId('field-name'));
    }

    public function testPreviewReturnsRawHtml(): void
    {
        $field = HtmlEditor::make('Content', 'HTML_BODY');

        self::assertSame('<b>test</b>', $field->previewValue('<b>test</b>'));
    }

    public function testFluentMethodsConfigureEditorParams(): void
    {
        $field = (new HtmlEditorFieldTestDouble('Content', 'HTML_BODY'))
            ->height(400)
            ->width('100%')
            ->editorPlaceholder('Enter HTML')
            ->showTaskbar()
            ->autoResize(true, 800, 40)
            ->view('code')
            ->siteId('s1');

        $params = $field->exposeEditorParams('<p>x</p>');

        self::assertSame(400, $params['height']);
        self::assertSame('100%', $params['width']);
        self::assertSame('Enter HTML', $params['placeholder']);
        self::assertTrue($params['showTaskbars']);
        self::assertTrue($params['autoResize']);
        self::assertSame(800, $params['autoResizeMaxHeight']);
        self::assertSame(40, $params['autoResizeOffset']);
        self::assertSame('code', $params['view']);
        self::assertSame('s1', $params['siteId']);
        self::assertSame('HTML_BODY', $params['inputName']);
    }

    public function testHeightFromRowsWhenNotExplicit(): void
    {
        $field = new HtmlEditorFieldTestDouble('Content', 'BODY');
        $field->rows(10);

        $params = $field->exposeEditorParams('');

        self::assertSame(220, $params['height']);
    }

    public function testInvalidViewFallsBackToWysiwyg(): void
    {
        $field = (new HtmlEditorFieldTestDouble('Content', 'BODY'))->view('unknown');

        self::assertSame('wysiwyg', $field->exposeEditorParams('')['view']);
    }

    public function testFieldPlaceholderIsUsedForEditorWhenEditorPlaceholderNotSet(): void
    {
        $field = (new HtmlEditorFieldTestDouble('Content', 'HTML_BODY'))->placeholder('From field');

        self::assertSame('From field', $field->exposeEditorParams('')['placeholder']);
    }

    public function testExtendsTextareaAndUsesPlaceholderAttrInFallback(): void
    {
        $html = HtmlEditor::make('Content', 'HTML_BODY')
            ->disableEditor()
            ->placeholder('Type here')
            ->renderFormField('');

        self::assertStringContainsString('placeholder="Type here"', $html);
    }
}

final class HtmlEditorFieldTestDouble extends HtmlEditor
{
    /** @return array<string, mixed> */
    public function exposeEditorParams(string $value): array
    {
        return $this->buildHtmlEditorParams($this->column, $value, $this->rows, $this->placeholder);
    }

    public function exposeEditorId(string $inputName): string
    {
        return $this->buildHtmlEditorId($inputName);
    }
}
