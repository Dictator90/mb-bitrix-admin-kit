<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\FieldConditionContext;
use PHPUnit\Framework\TestCase;

final class FieldConditionContextTest extends TestCase
{
    public function testHelpers(): void
    {
        $ctx = FieldConditionContext::fromArray(['ACTIVE' => 'Y', 'TYPE' => 'manual', '_mode' => 'edit', '_id' => '5']);
        self::assertSame('Y', $ctx->get('ACTIVE'));
        self::assertTrue($ctx->has('TYPE'));
        self::assertTrue($ctx->is('ACTIVE', 'Y'));
        self::assertTrue($ctx->isNot('ACTIVE', 'N'));
        self::assertTrue($ctx->in('TYPE', ['manual']));
        self::assertFalse($ctx->isCreate());
        self::assertTrue($ctx->isEdit());
    }
}
