<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\UI;

interface RenderableContract
{
    public function render(): string;
}
