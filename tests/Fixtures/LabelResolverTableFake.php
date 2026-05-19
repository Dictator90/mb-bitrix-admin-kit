<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Fixtures;

final class LabelResolverTableFake
{
    public static function getEntity(): LabelResolverEntityFake
    {
        return new LabelResolverEntityFake();
    }
}

final class LabelResolverEntityFake
{
    public function hasField(string $name): bool
    {
        return in_array($name, ['USER_ID', 'ID'], true);
    }

    public function getField(string $name): LabelResolverFieldFake
    {
        return match ($name) {
            'USER_ID' => new LabelResolverFieldFake('User identifier'),
            'ID' => new LabelResolverFieldFake('Record identifier'),
            default => new LabelResolverFieldFake($name),
        };
    }
}

final class LabelResolverFieldFake
{
    public function __construct(private string $title)
    {
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
