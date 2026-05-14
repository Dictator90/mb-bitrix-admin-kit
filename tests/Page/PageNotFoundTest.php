<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Exception\PageNotFoundException;
use MB\Bitrix\AdminKit\Page\ResourcePageResolver;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class PageNotFoundTest extends TestCase
{
    public function testResolverThrowsWhenPageIsMissing(): void
    {
        $this->expectException(PageNotFoundException::class);

        (new ResourcePageResolver())->resolve(new ProductResource(), 'missing');
    }
}
