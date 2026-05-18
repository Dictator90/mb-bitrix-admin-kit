<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Grid\Grid;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class GridDoesNotBuildOrmParamsTest extends TestCase
{
    public function testGridHasNoOrmQueryMethods(): void
    {
        $reflection = new ReflectionClass(Grid::class);

        self::assertFalse($reflection->hasMethod('getOrmParams'));
        self::assertFalse($reflection->hasMethod('buildOrmFilter'));
    }
}
