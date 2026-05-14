<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Support;

use MB\Bitrix\AdminKit\Support\AdminCondition;
use PHPUnit\Framework\TestCase;

final class AdminConditionTest extends TestCase
{
    public function testItEvaluatesCallbacks(): void
    {
        self::assertTrue(AdminCondition::evaluate(fn (array $ctx) => $ctx['can'] === true, ['can' => true]));
        self::assertFalse(AdminCondition::evaluate(false));
    }
}
