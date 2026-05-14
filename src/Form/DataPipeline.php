<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Form;

use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Support\AdminCollection;

final class DataPipeline
{
    /**
     * @param iterable<FieldContract> $fields
     * @param array<string,mixed> $raw
     */
    public function process(iterable $fields, array $raw): FormData
    {
        $rawData = [];
        $normalized = [];
        $validated = [];
        $errors = [];

        foreach (AdminCollection::make($fields)->all() as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }

            $column = $field->getColumn();
            $rawData[$column] = $raw[$column] ?? null;
            $normalized[$column] = $field->normalize($rawData[$column]);
        }

        foreach (AdminCollection::make($fields)->all() as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }

            if (method_exists($field, 'isReadOnlyFor') ? $field->isReadOnlyFor($normalized) : $field->isReadOnly()) {
                continue;
            }

            $column = $field->getColumn();
            foreach ($field->runValidation($normalized[$column] ?? null, $normalized) as $message) {
                $errors[$column][] = $message;
            }

            $validated[$column] = $normalized[$column] ?? null;
        }

        return new FormData($rawData, $normalized, $validated, $errors);
    }
}
