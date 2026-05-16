<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use MB\Bitrix\AdminKit\Contracts\Page\ResourcePageContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Page\Concerns\HasPageAuthorization;
use MB\Bitrix\AdminKit\Page\Concerns\HasPageResource;

abstract class ResourcePage extends Page implements ResourcePageContract
{
    use HasPageAuthorization;
    use HasPageResource;

    /** @param array<string,mixed> $params */
    public function __construct(
        ?ResourceContract $resource = null,
        mixed $id = null,
        array $params = [],
    ) {
        parent::__construct($params);
        $this->resource = $resource;
        $this->id = $id;
    }

    public function title(): string
    {
        return $this->hasResource() ? $this->resource()->getTitle() : parent::title();
    }
}
