<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field;

use MB\Bitrix\AdminKit\Field\FieldRenderContext;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class FieldRenderContextTest extends TestCase
{
    public function testTextFieldUsesRenderContextOnIndexAndDetail(): void
    {
        $resource = new ProductResource();
        $field = Text::make('Name', 'NAME')->displayUsing(
            static fn (mixed $value, array $row, array $context): string => $context['page'] . ':' . $row['ID'] . ':' . $value,
        );
        $context = new FieldRenderContext($field, $resource, ['ID' => 5, 'NAME' => 'Item'], 'Item', 'index', ['ID' => 5]);

        self::assertSame('index:5:Item', $field->renderIndex($context));

        $detailContext = new FieldRenderContext($field, $resource, ['ID' => 5, 'NAME' => 'Item'], 'Item', 'detail', ['ID' => 5]);
        self::assertSame('detail:5:Item', $field->renderDetail($detailContext));
    }
}
