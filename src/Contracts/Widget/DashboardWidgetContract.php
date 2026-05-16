<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Widget;

use MB\Bitrix\AdminKit\Contracts\UI\ConditionalVisibilityContract;
use MB\Bitrix\AdminKit\Contracts\UI\HtmlAttributesContract;

interface DashboardWidgetContract extends WidgetContract, HtmlAttributesContract, ConditionalVisibilityContract
{
    public function label(string $label): static;

    public function icon(string $cssClass): static;

    public function span(int $columns): static;
}
