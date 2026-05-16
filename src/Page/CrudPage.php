<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Contracts\Page\CrudPageContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Support\Enums\PageType;

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

    /** @param array<string,mixed> $params */
    public function __construct(
        ?ResourceContract $resource = null,
        mixed $id = null,
        array $params = [],
    ) {
        parent::__construct($resource, $id, $params);
    }
}
