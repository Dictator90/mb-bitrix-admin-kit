<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\FieldRenderContext;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class FieldExistingApiPreservedTest extends TestCase
{
    public function testFieldExistingApiPreserved(): void
    {
        $field = Text::make('Name', 'NAME');
        $methods = [
            'default',
            'sortable',
            'editable',
            'hint',
            'help',
            'placeholder',
            'readonly',
            'exportable',
            'importable',
            'private',
            'system',
            'readonlyWhen',
            'multiple',
            'computed',
            'displayUsing',
            'displayValue',
            'renderIndex',
            'renderForm',
            'renderFormField',
            'renderDetail',
            'normalize',
            'serializePostValue',
            'visibleWhen',
            'renderHint',
        ];

        foreach ($methods as $method) {
            self::assertTrue(method_exists($field, $method), sprintf('Missing Field API method %s().', $method));
        }
    }

    public function testHelpIsBackwardCompatibleAliasForHint(): void
    {
        $field = Text::make('Name', 'NAME')->help('Helpful text');

        self::assertStringContainsString('data-hint="Helpful text"', $field->renderHint());

        $field->help(null);

        self::assertSame('', $field->renderHint());
    }

    public function testPrivateSystemExportableImportableFlagsArePreserved(): void
    {
        $field = Text::make('Secret', 'SECRET')->private()->system()->exportable(false)->importable(false);

        self::assertTrue($field->isPrivate());
        self::assertTrue($field->isSystem());
        self::assertFalse($field->isExportable());
        self::assertFalse($field->isImportable());
    }

    public function testFieldComputedStillWorks(): void
    {
        $field = Text::make('Status', 'STATUS')->computed(static fn (array $row): string => $row['ACTIVE'] === 'Y' ? 'yes' : 'no');

        self::assertTrue($field->isComputed());
        self::assertSame('yes', $field->computeValue(['ACTIVE' => 'Y']));
        self::assertFalse($field->getGridColumnConfig()['sort']);
    }

    public function testFieldDisplayUsingStillWorks(): void
    {
        $field = Text::make('Name', 'NAME')->displayUsing(
            static fn (mixed $value, array $row, array $context): string => $value . '-' . $row['ID'] . '-' . $context['page'],
        );
        $context = new FieldRenderContext($field, new ProductResource(), ['ID' => 7, 'NAME' => 'Phone'], 'Phone', 'detail', ['ID' => 7]);

        self::assertSame('Phone-7-detail', $field->displayValue('Phone', ['ID' => 7], ['page' => 'detail']));
        self::assertSame('Phone-7-detail', $field->renderDetail($context));
    }

    public function testFieldNormalizeMultipleStillWorks(): void
    {
        $multiple = Text::make('Tags', 'TAGS')->multiple();
        $scalar = Text::make('Name', 'NAME');

        self::assertSame([], $multiple->normalize(null));
        self::assertSame(['a'], $multiple->normalize('a'));
        self::assertSame(['a', 'b'], $multiple->normalize(['a', 'b']));
        self::assertSame('a', $scalar->normalize(['a', 'b']));
    }

    public function testFieldSerializePostValueDelegatesToNormalize(): void
    {
        $field = Text::make('Tags', 'TAGS')->multiple();

        self::assertSame(['a'], $field->serializePostValue('a'));
    }

    public function testFieldRenderContextFallbackTest(): void
    {
        $field = Text::make('Name', 'NAME')->displayUsing(static fn (mixed $value): string => '<b>' . $value . '</b>');
        $context = new FieldRenderContext($field, new ProductResource(), ['NAME' => 'Phone'], 'Phone', 'index', ['NAME' => 'Phone']);

        self::assertSame('&lt;b&gt;Phone&lt;/b&gt;', $field->renderIndex($context));
    }

    public function testRenderIndexAndDetailEscapeHtmlAfterPreviewValue(): void
    {
        $field = Text::make('Name', 'NAME')->displayUsing(static fn (): string => '<script>alert(1)</script>');
        $context = new FieldRenderContext($field, new ProductResource(), ['NAME' => 'Phone'], 'Phone', 'detail', ['NAME' => 'Phone']);

        self::assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $field->renderIndex('Phone', ['NAME' => 'Phone']));
        self::assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $field->renderDetail($context));
    }

    public function testFieldRenderFormUsesRenderFormFieldFallback(): void
    {
        $field = Text::make('Name', 'NAME');
        $context = new FieldRenderContext($field, new ProductResource(), ['NAME' => 'Phone'], 'Phone', 'form', ['NAME' => 'Phone']);

        self::assertStringContainsString('value="Phone"', $field->renderForm($context));
    }
}
