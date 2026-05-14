<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Url;

use MB\Bitrix\AdminKit\Support\UrlGenerator;
use PHPUnit\Framework\TestCase;

final class UrlRoutingV080Test extends TestCase
{
    public function testV080RoutesUseUrlGenerator(): void
    {
        $url = new UrlGenerator('/bitrix/admin/module.php');

        self::assertSame('/bitrix/admin/module.php?page=orders&action=list', $url->resourceUrl('orders', ['action' => 'list']));
        self::assertSame('/bitrix/admin/module.php?action=add', $url->createUrl());
        self::assertSame('/bitrix/admin/module.php?action=detail&id=5', $url->detailUrl(5));
        self::assertSame('/bitrix/admin/module.php?action=bulk&bulk_action=delete', $url->bulkActionUrl('delete'));
        self::assertSame('/bitrix/admin/module.php?action=import', $url->importUrl());
        self::assertSame('/bitrix/admin/module.php?action=export', $url->exportUrl());
    }
}
