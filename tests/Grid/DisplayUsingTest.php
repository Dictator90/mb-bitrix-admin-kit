<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Grid;

use MB\Bitrix\AdminKit\Field\Text;
use PHPUnit\Framework\TestCase;

final class DisplayUsingTest extends TestCase
{
    public function testItUsesDisplayCallbackForIndexAndDetail(): void
    {
        $field = Text::make('Status', 'STATUS')->displayUsing(
            fn(mixed $value, array $row, array $context): string => $context['page'] . ':' . $row['NAME'] . ':' . $value
        );

        self::assertSame('index:Item:Y', $field->renderIndex('Y', ['NAME' => 'Item']));
        self::assertSame('detail:Item:Y', $field->renderDetail('Y', ['NAME' => 'Item']));
    }
}
