<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page\Standalone\Services;

use JsonException;
use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;

final class OptionSerializer
{
    public function serialize(FieldContract $field, mixed $value): string
    {
        if (method_exists($field, 'serializeOptionValue')) {
            return (string)$field->serializeOptionValue($value);
        }

        if (is_array($value)) {
            try {
                return (string)json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } catch (JsonException) {
                return '[]';
            }
        }

        return (string)$value;
    }

    public function unserialize(FieldContract $field, string $value): mixed
    {
        if (method_exists($field, 'unserializeOptionValue')) {
            return $field->unserializeOptionValue($value);
        }

        if ($value !== '' && ($value[0] === '[' || $value[0] === '{')) {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (JsonException) {
                // Keep scalar string when stored value is not valid JSON.
            }
        }

        return $value;
    }
}
