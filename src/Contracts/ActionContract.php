<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts;

interface ActionContract
{
    public function getId(): string;

    public function getLabel(): string;
}
