<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
use MB\Bitrix\AdminKit\Contracts\Page\CrudPageContract;
use MB\Bitrix\AdminKit\Contracts\Resource\CrudResourceContract;
use MB\Bitrix\AdminKit\Contracts\Resource\ResourceOrmContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use RuntimeException;

abstract class CrudPage extends ResourcePage implements CrudPageContract
{
    protected bool $isAsync = true;

    public function isAsync(): bool
    {
        return $this->isAsync;
    }

    /** @return iterable<FieldContract> */
    public function fields(): iterable
    {
        return [];
    }

    /** @param array<string,mixed> $params */
    public function __construct(
        ?ResourceContract $resource = null,
        mixed $id = null,
        array $params = [],
    ) {
        parent::__construct($resource, $id, $params);
        $this->pageType = static::defaultPageType();
    }

    /**
     * CRUD pages require the complete CRUD resource surface, while
     * ResourcePageContract deliberately keeps its public resource accessor
     * typed to the generic ResourceContract.
     */
    public function resource(): CrudResourceContract
    {
        $resource = parent::resource();

        if (!$resource instanceof CrudResourceContract) {
            throw new RuntimeException(sprintf(
                '%s requires an instance of %s; %s was provided.',
                static::class,
                CrudResourceContract::class,
                $resource::class,
            ));
        }

        return $resource;
    }

    public function getResource(): CrudResourceContract
    {
        return $this->resource();
    }

    /**
     * @internal CRUD page helpers that address a persisted record need an
     * ORM-backed resource. Keep that requirement explicit instead of relying
     * on a method that is not part of CrudResourceContract.
     */
    protected function resourcePrimaryKey(): string
    {
        $resource = $this->resource();

        if (!$resource instanceof ResourceOrmContract) {
            throw new RuntimeException(sprintf(
                '%s requires an ORM-backed resource to resolve a primary key; %s was provided.',
                static::class,
                $resource::class,
            ));
        }

        return $resource->getPrimaryKey();
    }

    /**
     * The PageType the page operates on. Concrete CRUD pages declare this
     * once instead of repeating `$this->pageType = ...` in their constructor.
     */
    abstract protected static function defaultPageType(): PageType;

    /** @return array<int,FieldContract> */
    protected function visibleFields(PageType $pageType): array
    {
        $fields = [];
        foreach ($this->fields() as $field) {
            if ($field instanceof FieldContract && $field->isVisibleOn($pageType)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Default list of fields visible on the current page. Concrete pages
     * (e.g. FormPage) may override to support layout containers.
     *
     * @return FieldContract[]
     */
    protected function getVisibleFields(): array
    {
        return $this->visibleFields($this->pageType ?? static::defaultPageType());
    }

    /**
     * Renders a Bitrix `ui.info.error` screen — used for "not found",
     * "permission denied" and other situations that should replace the
     * normal page content with a standard error block.
     */
    protected function renderError(string $message): void
    {
        global $APPLICATION;
        $APPLICATION->IncludeComponent('bitrix:ui.info.error', '', [
            'TITLE' => $message,
        ]);
    }

    /** Alias kept for child overrides that historically used this name. */
    protected function renderPermissionError(string $message): void
    {
        $this->renderError($message);
    }
}
