<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

final readonly class EntityObjectSaveResult
{
    /**
     * @param array<int,string> $globalErrors
     * @param array<string,list<string>> $fieldErrors
     */
    public function __construct(
        public bool $success,
        public mixed $savedId = null,
        public array $globalErrors = [],
        public array $fieldErrors = [],
    ) {
    }
}
