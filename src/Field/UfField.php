<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use CUserTypeManager;
use MB\Bitrix\AdminKit\Support\AdminCollection;

class UfField extends Field
{
    protected string $entityId = '';

    /** @var array<string,mixed>|null */
    protected ?array $metadata = null;

    public function entityId(string $entityId): static
    {
        $this->entityId = $entityId;
        $this->metadata = null;

        return $this;
    }

    /** @param array<string,mixed> $metadata */
    public function metadata(array $metadata): static
    {
        $this->metadata = $metadata;
        $this->multiple((string)($metadata['MULTIPLE'] ?? 'N') === 'Y');
        $this->required((string)($metadata['MANDATORY'] ?? 'N') === 'Y');

        return $this;
    }

    public function renderFormField(mixed $value = null): string
    {
        $metadata = $this->getMetadata();
        $name = htmlspecialcharsbx($this->column . ($this->multiple ? '[]' : ''));
        $currentValue = $this->resolveValue($value);

        if (class_exists(CUserTypeManager::class) && isset($GLOBALS['USER_FIELD_MANAGER'])) {
            ob_start();
            $field = $metadata;
            $field['VALUE'] = $currentValue;
            $field['FIELD_NAME'] = $this->column;
            $GLOBALS['USER_FIELD_MANAGER']->EditFormShowField($this->entityId, $field);
            return (string)ob_get_clean();
        }

        if ($this->multiple) {
            $values = AdminCollection::make(is_array($currentValue) ? $currentValue : ($currentValue === null ? [] : [$currentValue]))->all();
            $inputs = '';
            foreach ($values ?: [''] as $item) {
                $inputs .= '<input type="text" class="ui-ctl-element" name="' . $name . '" value="' . htmlspecialcharsbx((string)$item) . '">';
            }

            return '<div class="ui-ctl ui-ctl-textbox adminkit-uf-field adminkit-uf-field--multiple">' . $inputs . '</div>';
        }

        return '<div class="ui-ctl ui-ctl-textbox adminkit-uf-field"><input type="text" class="ui-ctl-element" name="'
            . $name . '" value="' . htmlspecialcharsbx((string)$currentValue) . '"></div>';
    }

    public function normalize(mixed $value): mixed
    {
        if ($this->multiple) {
            if ($value === null || $value === '') {
                return [];
            }

            return array_values(array_filter(AdminCollection::make(is_array($value) ? $value : [$value])->all(), static fn ($item): bool => $item !== null && $item !== ''));
        }

        if (is_array($value)) {
            $first = reset($value);
            return $first === false || $first === '' ? null : $first;
        }

        return $value === '' ? null : $value;
    }

    public function renderIndex(mixed $value, array $row = []): string
    {
        return $this->renderDetail($value, $row);
    }

    public function renderDetail(mixed $value, array $row = []): string
    {
        $display = $this->displayValue($value, $row, ['page' => 'detail', 'field' => $this]);
        if (is_array($display)) {
            return implode(', ', array_map(static fn ($item): string => htmlspecialcharsbx((string)$item), $display));
        }

        return htmlspecialcharsbx((string)($display ?? ''));
    }

    /** @return array<string,mixed> */
    protected function getMetadata(): array
    {
        if ($this->metadata !== null) {
            return $this->metadata;
        }

        if (class_exists(CUserTypeManager::class) && isset($GLOBALS['USER_FIELD_MANAGER']) && $this->entityId !== '') {
            $fields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields($this->entityId, 0, LANGUAGE_ID);
            $this->metadata = $fields[$this->column] ?? [];
        } else {
            $this->metadata = [];
        }

        $this->multiple((string)($this->metadata['MULTIPLE'] ?? 'N') === 'Y');
        $this->required((string)($this->metadata['MANDATORY'] ?? 'N') === 'Y');

        return $this->metadata;
    }
}
