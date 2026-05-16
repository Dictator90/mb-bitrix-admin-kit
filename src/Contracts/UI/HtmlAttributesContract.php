<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\UI;

interface HtmlAttributesContract
{
    public function class(string ...$classes): static;

    public function style(string $property, string $value): static;

    public function attr(string $name, string $value): static;
}
