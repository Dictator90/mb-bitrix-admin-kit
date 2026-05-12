<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

interface PageContract
{
    public function render(): void;

    public function getResource(): ResourceContract;
}
