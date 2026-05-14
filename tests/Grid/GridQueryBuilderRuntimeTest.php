<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use MB\Bitrix\AdminKit\Grid\GridContext;
use MB\Bitrix\AdminKit\Grid\GridQueryBuilder;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class GridQueryBuilderRuntimeTest extends TestCase
{
    public function testItPassesRuntimeObjectsThrough(): void
    {
        $runtime = new Reference('USER');
        $resource = new class($runtime) extends ProductResource {
            public function __construct(private object $runtime) {}

            public function runtimeFields(): array
            {
                return ['BASE_RUNTIME'];
            }

            public function indexRuntime(GridContext $context): array
            {
                return [$this->runtime];
            }
        };

        $params = (new GridQueryBuilder())->build($resource, GridContext::make($resource));

        self::assertSame(['BASE_RUNTIME', $runtime], $params['runtime']);
    }
}
