<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Contracts\Field;

interface FieldSerializationContract
{
    public function normalize(mixed $value): mixed;

    public function serializePostValue(mixed $value): mixed;
}
