<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Closure;
use MB\Bitrix\AdminKit\Relation\RelationType;
use MB\Bitrix\AdminKit\Support\LocalizedMessage;
use Throwable;

/**
 * Select one record from a DataManager table (foreign-key select).
 */
class BelongsTo extends RelationField
{
    protected string $dataManagerClass;
    protected string $titleColumn = 'NAME';
    protected string $valueColumn = 'ID';
    protected string $emptyLabel = '';
    /** @var array<string,mixed>|Closure|null */
    protected array|Closure|null $filter = [];
    protected array $order = [];
    protected ?Closure $optionsCallback = null;
    protected string $renderMode = 'select';

    public function __construct(string $label, ?string $column = null, string $dataManagerClass = '')
    {
        parent::__construct($label, $column);
        $this->dataManagerClass = $dataManagerClass;
    }

    public static function make(string $label, ?string $column = null, string $dataManagerClass = ''): static
    {
        return new static($label, $column, $dataManagerClass);
    }

    /** Column in the related table to display as option label (default: NAME). */
    public function titleColumn(string $column): static
    {
        $this->titleColumn = $column;
        return $this;
    }

    /** Column to use as option value (default: ID). */
    public function valueColumn(string $column): static
    {
        $this->valueColumn = $column;
        return $this;
    }

    /** Filter applied when loading options. */
    /** @param array<string,mixed>|Closure|null $filter */
    public function filter(array|Closure|null $filter): static
    {
        $this->filter = $filter;
        return $this;
    }

    /** Order options by a column. */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->order[$column] = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        return $this;
    }

    /** Add a blank first option. */
    public function emptyOption(string $label = ''): static
    {
        $this->emptyLabel = $label !== '' ? $label : LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_BELONGS_TO_EMPTY_OPTION', '— select —');
        return $this;
    }

    /** Provide a custom callback to build options: fn(): array<scalar, string>. */
    public function options(Closure $callback): static
    {
        $this->optionsCallback = $callback;
        return $this;
    }

    protected function loadOptions(): array
    {
        if ($this->optionsCallback !== null) {
            return ($this->optionsCallback)();
        }

        if (!$this->dataManagerClass || !class_exists($this->dataManagerClass)) {
            return [];
        }

        $params = [
            'select' => array_unique([$this->valueColumn, $this->titleColumn]),
        ];
        if (is_array($this->filter) && !empty($this->filter)) {
            $params['filter'] = $this->filter;
        }
        if (!empty($this->order)) {
            $params['order'] = $this->order;
        }

        $options = [];
        try {
            $result = $this->dataManagerClass::getList($params);
            while ($row = $result->fetch()) {
                $options[(string)$row[$this->valueColumn]] = (string)($row[$this->titleColumn] ?? '');
            }
        } catch (Throwable) {
        }

        return $options;
    }

    public function isToMany(): bool
    {
        return false;
    }

    public function relationType(): RelationType
    {
        return RelationType::BELONGS_TO;
    }

    public function asSelect(): static
    {
        $this->renderMode = 'select';
        return $this;
    }
    public function asEntitySelector(): static
    {
        $this->renderMode = 'entity_selector';
        return $this;
    }
    public function asRadio(): static
    {
        $this->renderMode = 'radio';
        return $this;
    }
    public function asLink(): static
    {
        $this->renderMode = 'link';
        return $this;
    }

    public function renderFormField(mixed $value = null): string
    {
        $current = (string)($this->resolveValue($value) ?? '');
        $name = htmlspecialcharsbx($this->column);
        $options = $this->loadOptions();
        $reactive = $this->renderReactiveAttrs();
        $required = $this->required ? ' required' : '';

        $optionsHtml = '';
        if ($this->emptyLabel !== '') {
            $sel = $current === '' ? ' selected' : '';
            $optionsHtml .= '<option value=""' . $sel . '>' . htmlspecialcharsbx($this->emptyLabel) . '</option>';
        }
        foreach ($options as $optVal => $optLabel) {
            $sel = (string) $optVal === (string) $current ? ' selected' : '';
            $optionsHtml .= '<option value="' . htmlspecialcharsbx($optVal) . '"' . $sel . '>'
                . htmlspecialcharsbx($optLabel) . '</option>';
        }

        return <<<HTML
        <div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
            <div class="ui-ctl-after ui-ctl-icon-angle"></div>
            <select class="ui-ctl-element" name="{$name}"{$required}{$reactive}>{$optionsHtml}</select>
        </div>
        HTML;
    }

    public function previewValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return LocalizedMessage::get(__FILE__, 'MB_ADMIN_KIT_BELONGS_TO_EMPTY_PREVIEW', '—');
        }
        if ($this->optionsCallback !== null) {
            $options = ($this->optionsCallback)();
            return htmlspecialcharsbx($options[(string)$value] ?? (string)$value);
        }
        if (!$this->dataManagerClass || !class_exists($this->dataManagerClass)) {
            return htmlspecialcharsbx((string)$value);
        }
        try {
            $row = $this->dataManagerClass::getList([
                'select' => [$this->titleColumn],
                'filter' => [$this->valueColumn => $value],
                'limit' => 1,
            ])->fetch();
            if ($row) {
                return htmlspecialcharsbx((string)($row[$this->titleColumn] ?? $value));
            }
        } catch (Throwable) {
        }

        return htmlspecialcharsbx((string)$value);
    }


    protected function supportsInlineEdit(): bool
    {
        return false;
    }

    public function getFilterType(): ?string
    {
        return 'list';
    }

}
