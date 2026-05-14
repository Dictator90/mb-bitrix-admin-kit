<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

interface PageContract
{
    public static function pageName(): string;

    public function render(): void;

    public function resource(): ResourceContract;

    public function getResource(): ResourceContract;
}
