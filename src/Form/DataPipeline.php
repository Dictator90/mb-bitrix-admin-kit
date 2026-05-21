<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Form;

use MB\Bitrix\AdminKit\Contracts\Field\FieldContract;
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

        $formContext = $this->formContextForReadonly($raw, $normalized);

        foreach (AdminCollection::make($fields)->all() as $field) {
            if (!$field instanceof FieldContract) {
                continue;
            }

            if ($field->isReadOnlyFor($formContext)) {
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

    /**
     * @param array<string,mixed> $raw
     * @param array<string,mixed> $normalized
     * @return array<string,mixed>
     */
    private function formContextForReadonly(array $raw, array $normalized): array
    {
        $context = $normalized;
        foreach (['_mode', '_id', 'ID'] as $key) {
            if (array_key_exists($key, $raw)) {
                $context[$key] = $raw[$key];
            }
        }

        return $context;
    }
}
