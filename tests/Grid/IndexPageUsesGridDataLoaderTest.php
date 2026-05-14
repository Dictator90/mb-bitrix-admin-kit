<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Page\IndexPage;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class IndexPageUsesGridDataLoaderTest extends TestCase
{
    public function testLoadDataDelegatesToGridDataLoader(): void
    {
        $method = new ReflectionMethod(IndexPage::class, 'loadData');
        $lines = file($method->getFileName());
        $source = implode('', array_slice($lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1));

        self::assertStringContainsString('new GridDataLoader()', $source);
        self::assertStringNotContainsString('getList(', $source);
        self::assertStringNotContainsString('getCount(', $source);
        self::assertStringNotContainsString('new GridQueryBuilder()', $source);
    }
}
