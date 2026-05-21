<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\FieldRenderContext;
use MB\Bitrix\AdminKit\Field\Select;
use MB\Bitrix\AdminKit\Field\Text;
use PHPUnit\Framework\TestCase;

final class FieldReadonlyRenderTest extends TestCase
{
    public function testTextUsesReadonlyOnUpdateInFormRender(): void
    {
        $field = Text::make('Title', 'TITLE')->readonlyOnUpdate();
        $context = new FieldRenderContext(
            field: $field,
            resource: new ReadonlyRenderTestResource(),
            value: 'Old',
            page: 'form',
            row: ['ID' => 5],
            meta: ['mode' => 'edit', 'formData' => ['_mode' => 'edit', '_id' => '5']],
        );

        $html = $field->renderForm($context);

        self::assertStringContainsString('readonly', $html);
        self::assertStringContainsString('disabled', $html);
    }

    public function testSelectUsesConditionalReadonlyInFormRender(): void
    {
        $field = Select::make('Status', 'STATUS')->options(['a' => 'A'])->readonlyOnCreate();
        $context = new FieldRenderContext(
            field: $field,
            resource: new ReadonlyRenderTestResource(),
            value: null,
            page: 'form',
            meta: ['mode' => 'create', 'formData' => ['_mode' => 'create']],
        );

        $html = $field->renderForm($context);

        self::assertStringContainsString('disabled', $html);
    }
}

final class ReadonlyRenderTestResource extends \MB\Bitrix\AdminKit\Tests\Fixtures\ManagerBaseTestResource
{
    public static function getId(): string
    {
        return 'readonly_render_test';
    }
}
