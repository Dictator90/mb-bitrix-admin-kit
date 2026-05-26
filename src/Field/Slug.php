<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use MB\Bitrix\AdminKit\Support\AdminString;

class Slug extends Text
{
    /** @var list<string> */
    protected array $fromColumns = [];

    protected string $separator = '-';

    protected ?string $pendingGenerated = null;

    protected bool $shouldApplyPending = false;

    public function separator(string $separator): static
    {
        $this->separator = $separator !== '' ? $separator : '-';

        return $this;
    }

    /** @param string|list<string> $sourceColumns */
    public function from(string|array $sourceColumns): static
    {
        $columns = array_values(array_filter(array_map(
            static fn (mixed $column): string => is_scalar($column) ? (string) $column : '',
            (array) $sourceColumns,
        ), static fn (string $column): bool => $column !== ''));

        $this->fromColumns = $columns;

        if ($columns === []) {
            return $this;
        }

        return $this->dependsOn($columns, function (self $field, mixed $value, array $formData): void {
            unset($value);

            $generated = $field->generateFromData($formData);
            $field->pendingGenerated = $generated;
            $field->shouldApplyPending = $field->shouldOverwriteFromDependency($formData);
        });
    }

    /** @param array<string,mixed> $formData */
    public function renderFormField(mixed $value = null, array $formData = []): string
    {
        $resolvedValue = (string) $this->resolveValue($value);
        $generated = $this->pendingGenerated;

        if ($generated === null && $this->fromColumns !== []) {
            $generated = $this->generateFromData($formData);
        }

        if ($this->shouldApplyPending && $generated !== null) {
            $resolvedValue = $generated;
        }

        $name = htmlspecialcharsbx($this->column);
        $val = htmlspecialcharsbx($resolvedValue);
        $maxAttr = $this->maxLength ? ' maxlength="' . $this->maxLength . '"' : '';
        $reqAttr = $this->requiredAttr();
        $readonlyAttr = $this->formReadonlyAttr($formData);
        $placeholderAttr = $this->placeholder !== null ? ' placeholder="' . htmlspecialcharsbx($this->placeholder) . '"' : '';
        $reactiveAttrs = $this->renderReactiveAttrs();

        $generatedStateName = htmlspecialcharsbx($this->generatedStateFieldName());
        $generatedStateValue = htmlspecialcharsbx((string) ($generated ?? ''));

        $html = <<<HTML
        <div class="ui-ctl ui-ctl-textbox">
            <input type="text" class="ui-ctl-element" name="{$name}" value="{$val}"{$maxAttr}{$reqAttr}{$readonlyAttr}{$placeholderAttr}{$reactiveAttrs}>
        </div>
        <input type="hidden" name="{$generatedStateName}" value="{$generatedStateValue}">
        HTML;

        $this->pendingGenerated = null;
        $this->shouldApplyPending = false;

        return $html;
    }

    /** @param array<string,mixed> $formData */
    protected function generateFromData(array $formData): string
    {
        if ($this->fromColumns === []) {
            return '';
        }

        $parts = [];
        foreach ($this->fromColumns as $column) {
            if (!array_key_exists($column, $formData)) {
                continue;
            }

            $value = $formData[$column];
            if (is_array($value)) {
                $value = implode(' ', array_map(
                    static fn (mixed $item): string => is_scalar($item) ? (string) $item : '',
                    $value,
                ));
            } elseif (!is_scalar($value) && $value !== null) {
                continue;
            }

            $stringValue = trim((string) ($value ?? ''));
            if ($stringValue !== '') {
                $parts[] = $stringValue;
            }
        }

        return $this->slugify(implode(' ', $parts));
    }

    /** @param array<string,mixed> $formData */
    protected function shouldOverwriteFromDependency(array $formData): bool
    {
        $currentValue = (string) ($formData[$this->column] ?? '');
        if ($currentValue === '') {
            return true;
        }

        $previousGenerated = (string) ($formData[$this->generatedStateFieldName()] ?? '');

        return $previousGenerated !== '' && $currentValue === $previousGenerated;
    }

    protected function slugify(string $value): string
    {
        return trim(AdminString::slug($value, $this->separator), $this->separator);
    }

    protected function generatedStateFieldName(): string
    {
        return '__adminkit_slug_generated_' . $this->column;
    }
}
