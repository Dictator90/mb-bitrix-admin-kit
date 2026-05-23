<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\FieldConditionContext;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use PHPUnit\Framework\TestCase;

final class FieldVisibleClosureTest extends TestCase
{
    public function testCanSeeAlias(): void
    {
        $field = Text::make('Comment', 'COMMENT')->canSee(static fn (FieldConditionContext $ctx): bool => $ctx->is('ACTIVE', 'Y'));
        self::assertTrue($field->isVisibleFor(['ACTIVE' => 'Y'], PageType::FORM));
        self::assertFalse($field->isVisibleFor(['ACTIVE' => 'N'], PageType::FORM));
    }
}
