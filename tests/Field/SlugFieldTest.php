<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\Slug;
use PHPUnit\Framework\TestCase;

final class SlugFieldTest extends TestCase
{
    public function testAppliesDependencyAndGeneratesSlugWhenCurrentValueIsEmpty(): void
    {
        $field = Slug::make('Slug', 'SLUG')->from('NAME');

        $field->applyDependency([
            'NAME' => 'Example Product',
            'SLUG' => '',
        ]);

        $html = $field->renderFormField('', ['NAME' => 'Example Product', 'SLUG' => '']);

        self::assertStringContainsString('name="SLUG"', $html);
        self::assertStringContainsString('value="example-product"', $html);
        self::assertStringContainsString('name="__adminkit_slug_generated_SLUG"', $html);
        self::assertStringContainsString('value="example-product"', $html);
    }

    public function testDoesNotOverrideManualValueWhenItDiffersFromPreviousGeneratedSlug(): void
    {
        $field = Slug::make('Slug', 'SLUG')->from('NAME');

        $field->applyDependency([
            'NAME' => 'New Title',
            'SLUG' => 'custom-manual-slug',
            '__adminkit_slug_generated_SLUG' => 'old-title',
        ]);

        $html = $field->renderFormField('custom-manual-slug', [
            'NAME' => 'New Title',
            'SLUG' => 'custom-manual-slug',
            '__adminkit_slug_generated_SLUG' => 'old-title',
        ]);

        self::assertStringContainsString('value="custom-manual-slug"', $html);
        self::assertStringContainsString('name="__adminkit_slug_generated_SLUG"', $html);
        self::assertStringContainsString('value="new-title"', $html);
    }

    public function testOverridesValueWhenCurrentEqualsPreviousGeneratedSlug(): void
    {
        $field = Slug::make('Slug', 'SLUG')->from('NAME');

        $field->applyDependency([
            'NAME' => 'New Title',
            'SLUG' => 'old-title',
            '__adminkit_slug_generated_SLUG' => 'old-title',
        ]);

        $html = $field->renderFormField('old-title', [
            'NAME' => 'New Title',
            'SLUG' => 'old-title',
            '__adminkit_slug_generated_SLUG' => 'old-title',
        ]);

        self::assertStringContainsString('name="SLUG"', $html);
        self::assertStringContainsString('value="new-title"', $html);
    }

    public function testDependsOnWithoutModifierWorksLikeFrom(): void
    {
        $field = Slug::make('Slug', 'SLUG')->dependsOn('NAME');

        $field->applyDependency([
            'NAME' => 'Example Product',
            'SLUG' => '',
        ]);

        $html = $field->renderFormField('', ['NAME' => 'Example Product', 'SLUG' => '']);

        self::assertStringContainsString('value="example-product"', $html);
    }

    public function testDependsOnWithoutModifierMergesMultipleSources(): void
    {
        $field = Slug::make('Slug', 'SLUG')->dependsOn(['FIRST', 'SECOND']);

        $field->applyDependency([
            'FIRST' => 'Hello',
            'SECOND' => 'World',
            'SLUG' => '',
        ]);

        $html = $field->renderFormField('', [
            'FIRST' => 'Hello',
            'SECOND' => 'World',
            'SLUG' => '',
        ]);

        self::assertStringContainsString('value="hello-world"', $html);
    }

    public function testGeneratesSlugFromCyrillicSource(): void
    {
        if (!class_exists(\CUtil::class) || !method_exists(\CUtil::class, 'translit')) {
            self::markTestSkipped('CUtil::translit is not available.');
        }

        $field = Slug::make('Slug', 'SLUG')->from('NAME');

        $field->applyDependency([
            'NAME' => 'Пример товара',
            'SLUG' => '',
        ]);

        $html = $field->renderFormField('', ['NAME' => 'Пример товара', 'SLUG' => '']);

        self::assertStringContainsString('value="primer-tovara"', $html);
    }

    public function testCustomDependsOnModifierDoesNotAddSourceColumn(): void
    {
        $field = Slug::make('Slug', 'SLUG')->dependsOn('CATEGORY_ID', static function (Slug $field, mixed $value, array $formData): void {
            unset($value, $formData);
            $field->separator('_');
        });

        $field->applyDependency([
            'CATEGORY_ID' => '1',
            'SLUG' => '',
        ]);

        $html = $field->renderFormField('', ['CATEGORY_ID' => '1', 'SLUG' => '']);

        self::assertStringContainsString('value=""', $html);
    }

    public function testSupportsCustomSeparator(): void
    {
        $field = Slug::make('Slug', 'SLUG')->separator('_')->from('NAME');

        $field->applyDependency([
            'NAME' => 'My test value',
            'SLUG' => '',
        ]);

        $html = $field->renderFormField('', ['NAME' => 'My test value', 'SLUG' => '']);

        self::assertStringContainsString('value="my_test_value"', $html);
    }
}
