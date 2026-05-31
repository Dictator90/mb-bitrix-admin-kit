<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Email;
use MB\Bitrix\AdminKit\Field\Number;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Field\Textarea;
use PHPUnit\Framework\TestCase;

final class FieldAttributesTest extends TestCase
{
    public function testUiCtlWrapperGetsFullWidthClassByDefault(): void
    {
        $html = Text::make('Name', 'NAME')->renderFormField('');
        self::assertStringContainsString('class="ui-ctl ui-ctl-textbox ui-ctl-w100"', $html);

        $textarea = Textarea::make('Desc', 'DESC')->renderFormField('');
        self::assertStringContainsString('class="ui-ctl ui-ctl-textarea ui-ctl-w100"', $textarea);

        $email = Email::make('Email', 'EMAIL')->renderFormField('');
        self::assertStringContainsString('class="ui-ctl ui-ctl-textbox ui-ctl-w100"', $email);

        $select = Select::make('Type', 'TYPE')->options(['a' => 'A'])->renderFormField('a');
        self::assertStringContainsString('class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown ui-ctl-w100"', $select);
    }

    public function testWithoutFullWidthDisablesAutomaticClass(): void
    {
        $html = Text::make('Name', 'NAME')->withoutFullWidth()->renderFormField('');
        self::assertStringContainsString('class="ui-ctl ui-ctl-textbox"', $html);
        self::assertStringNotContainsString('ui-ctl-w100', $html);
    }

    public function testCustomWrapperClassAndStyleAreAppended(): void
    {
        $html = Text::make('Name', 'NAME')
            ->wrapperClass('my-wrapper', 'extra')
            ->wrapperStyle('margin-top: 4px')
            ->renderFormField('');

        self::assertStringContainsString('class="ui-ctl ui-ctl-textbox ui-ctl-w100 my-wrapper extra"', $html);
        self::assertStringContainsString('style="margin-top: 4px"', $html);
    }

    public function testCustomElementClassStyleAndAttributesAreRendered(): void
    {
        $html = Text::make('Name', 'NAME')
            ->class('my-input')
            ->style('color: red')
            ->customAttributes([
                'data-foo' => 'bar',
                'aria-label' => 'My field',
            ])
            ->renderFormField('value');

        self::assertStringContainsString('class="ui-ctl-element my-input"', $html);
        self::assertStringContainsString('style="color: red"', $html);
        self::assertStringContainsString('data-foo="bar"', $html);
        self::assertStringContainsString('aria-label="My field"', $html);
    }

    public function testCustomAttributesMergesClassAndStyleKeys(): void
    {
        $html = Number::make('Qty', 'QTY')
            ->customWrapperAttributes([
                'class' => 'one two',
                'style' => 'padding: 4px',
                'data-x' => '1',
            ])
            ->renderFormField(0);

        self::assertStringContainsString('class="ui-ctl ui-ctl-textbox ui-ctl-w100 one two"', $html);
        self::assertStringContainsString('style="padding: 4px"', $html);
        self::assertStringContainsString('data-x="1"', $html);
    }

    public function testCustomAttributesReplaceClearsPreviousState(): void
    {
        $field = Text::make('Name', 'NAME')
            ->class('first')
            ->customAttributes(['data-keep' => '1']);

        $html = $field
            ->customAttributes(['class' => 'fresh', 'data-fresh' => 'y'], replace: true)
            ->renderFormField('');

        self::assertStringContainsString('class="ui-ctl-element fresh"', $html);
        self::assertStringContainsString('data-fresh="y"', $html);
        self::assertStringNotContainsString('data-keep', $html);
        self::assertStringNotContainsString('first', $html);
    }

    public function testAttributeValuesAreEscaped(): void
    {
        $html = Text::make('Name', 'NAME')
            ->customAttributes(['data-payload' => '"><script>alert(1)</script>'])
            ->renderFormField('');

        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringContainsString('data-payload="', $html);
    }

    public function testUnsafeAttributeNamesAreIgnored(): void
    {
        $html = Text::make('Name', 'NAME')
            ->customAttributes(['bad name' => 'x', 'data-ok' => 'y'])
            ->renderFormField('');

        self::assertStringNotContainsString('bad name', $html);
        self::assertStringContainsString('data-ok="y"', $html);
    }
}
