<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Concerns;

use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use RuntimeException;

trait HasPageResource
{
    protected ?ResourceContract $resource = null;

    protected mixed $id = null;

    /** @var array<string,mixed> */
    protected array $params = [];

    public function resource(): ResourceContract
    {
        if (!$this->hasResource()) {
            throw new RuntimeException('Resource is not set.');
        }

        return $this->resource;
    }

    public function getResource(): ResourceContract
    {
        return $this->resource();
    }

    public function setResource(ResourceContract $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    public function hasResource(): bool
    {
        return $this->resource !== null;
    }

    public function setContext(mixed $id = null, array $params = []): static
    {
        $this->id = $id;
        $this->params = $params;

        return $this;
    }
}
