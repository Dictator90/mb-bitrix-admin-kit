<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Concerns;

trait HasPageAssets
{
    /** @var array<int,string> */
    protected array $extensions = [];

    /** @param array<int,string> $extensions */
    protected function loadAssets(array $extensions = []): void
    {
        $extensions = array_values(array_unique([...$this->extensions, ...$extensions]));
        if ($extensions === []) {
            return;
        }

        if (class_exists(\Bitrix\Main\UI\Extension::class)) {
            \Bitrix\Main\UI\Extension::load($extensions);
        }
    }
}
