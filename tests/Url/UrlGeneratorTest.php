<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Url;

use MB\Bitrix\AdminKit\Support\UrlGenerator;
use PHPUnit\Framework\TestCase;

final class UrlGeneratorTest extends TestCase
{
    public function testItBuildsUrlsWithHttpBuildQuery(): void
    {
        $url = (new UrlGenerator('/admin/product.php?page=product'))->editUrl(10, ['tab' => 'main']);
        self::assertSame('/admin/product.php?page=product&action=edit&id=10&tab=main', $url);
    }
}
