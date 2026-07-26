<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Resource;

use PHPUnit\Framework\TestCase;
use Vendor\Demo\Admin\SettingsResource;
use Vendor\Demo\Orm\SettingsTable;

require_once dirname(__DIR__, 2) . '/examples/demo-module/lib/Admin/SettingsResource.php';
require_once dirname(__DIR__, 2) . '/examples/demo-module/lib/Orm/SettingsTable.php';

final class DemoSettingsResourceExampleTest extends TestCase
{
    public function testCopyableSettingsResourceExposesTheCompleteCrudDefinition(): void
    {
        $resource = new SettingsResource();

        self::assertSame(SettingsTable::class, $resource->dataManagerClass());
        self::assertSame('vendor_demo_settings', SettingsTable::getTableName());
        self::assertSame(
            ['ID', 'CODE', 'NAME', 'SCOPE', 'VALUE', 'ACTIVE', 'SORT'],
            array_map(static fn ($field): string => $field->getName(), SettingsTable::getMap()),
        );
        self::assertTrue($resource->useSidePanel());
        self::assertSame(840, $resource->sidePanelWidth());
        self::assertSame(['SORT' => 'ASC', 'ID' => 'DESC'], $resource->defaultSort());
        self::assertSame(
            ['ID', 'CODE', 'NAME', 'SCOPE', 'ACTIVE', 'SORT'],
            array_map(static fn ($field): string => $field->getColumn(), iterator_to_array($resource->indexFields())),
        );
        self::assertSame(
            ['CODE', 'NAME', 'SCOPE', 'VALUE', 'ACTIVE', 'SORT'],
            array_map(static fn ($field): string => $field->getColumn(), iterator_to_array($resource->formFields())),
        );
        self::assertCount(4, iterator_to_array($resource->filters()));
        self::assertCount(3, iterator_to_array($resource->rowActions()));
        self::assertCount(3, iterator_to_array($resource->bulkActions()));
    }
}
