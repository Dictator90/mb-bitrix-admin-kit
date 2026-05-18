<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Page;

use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

interface PagesContract
{
    /** @param iterable<class-string<PageContract>|PageContract> $pages */
    public static function make(iterable $pages): self;

    public function setResource(ResourceContract $resource): self;

    public function findByName(string $name): ?PageContract;

    public function findByType(PageType $type): ?PageContract;

    /** @param class-string<PageContract> $class */
    public function findByClass(string $class): ?PageContract;

    public function indexPage(): ?PageContract;

    public function formPage(): ?PageContract;

    public function detailPage(): ?PageContract;

    public function activePage(): ?PageContract;

    /** @return array<int, PageContract> */
    public function all(): array;
}
