<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Action;

interface BulkPanelItemContract
{
    public function getId(): string;

    public function getLabel(): string;

    public function getGroup(): string;

    public function getGroupLabel(): ?string;

    public function getSort(): int;

    public function isVisible(): bool;
}
