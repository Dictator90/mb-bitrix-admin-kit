<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Relation;

use Closure;
use MB\Bitrix\AdminKit\Contracts\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\FieldContract;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use Throwable;

/**
 * Renders a read-only table of related records inside a FormPage.
 *
 * Usage:
 *   HasMany::make('Заказы', OrderTable::class)
 *       ->foreignKey('USER_ID')         // column in OrderTable → current record's ID
 *       ->columns([
 *           ID::make('ID'),
 *           Text::make('Статус', 'STATUS'),
 *           Date::make('Дата', 'DATE_INSERT'),
 *       ])
 *       ->orderBy('DATE_INSERT', 'DESC')
 *       ->createUrl(fn($parentId) => '/bitrix/admin/order_edit.php?user_id=' . $parentId)
 *       ->editUrl(fn($row)   => '/bitrix/admin/order_edit.php?id=' . $row['ID'])
 *       ->deleteUrl(fn($row) => '/bitrix/admin/order_edit.php?delete=Y&id=' . $row['ID'])
 */
class HasMany implements ComponentContract
{
    protected string $label;
    protected string $dataManagerClass;
    protected string $foreignKey = 'ID';
    protected string $parentKey = 'ID';
    /** @var FieldContract[] */
    protected array $columns = [];
    protected array $order = [];
    protected ?int $limit = null;
    protected ?Closure $createUrlCallback = null;
    protected ?Closure $editUrlCallback = null;
    protected ?Closure $deleteUrlCallback = null;
    protected ?DataWrapper $item = null;
    protected PageType $pageType = PageType::FORM;
    protected ?array $visibleWhenRule = null;

    public function __construct(string $label, string $dataManagerClass)
    {
        $this->label = $label;
        $this->dataManagerClass = $dataManagerClass;
    }

    public static function make(string $label, string $dataManagerClass): static
    {
        return new static($label, $dataManagerClass);
    }

    /** Column in the related table that holds the parent's ID. */
    public function foreignKey(string $column): static
    {
        $this->foreignKey = $column;
        return $this;
    }

    /** Column in the current (parent) record used as the FK value (default: ID). */
    public function parentKey(string $column): static
    {
        $this->parentKey = $column;
        return $this;
    }

    /** @param FieldContract[] $columns Fields to display as table columns. */
    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->order[$column] = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /** Callback: fn(mixed $parentId): string — URL for "Add" button. */
    public function createUrl(Closure $callback): static
    {
        $this->createUrlCallback = $callback;
        return $this;
    }

    /** Callback: fn(array $row): string — URL for row "Edit" button. */
    public function editUrl(Closure $callback): static
    {
        $this->editUrlCallback = $callback;
        return $this;
    }

    /** Callback: fn(array $row): string — URL for row "Delete" button. */
    public function deleteUrl(Closure $callback): static
    {
        $this->deleteUrlCallback = $callback;
        return $this;
    }

    public function visibleWhen(string $column, mixed $value): static
    {
        $this->visibleWhenRule = is_array($value)
                ? ['column' => $column, 'values' => array_map('strval', $value)]
                : ['column' => $column, 'value' => (string)$value];
        return $this;
    }

    public function getVisibleWhen(): ?array
    {
        return $this->visibleWhenRule;
    }

    public function withItem(?DataWrapper $item): static
    {
        $this->item = $item;
        return $this;
    }

    public function withPageType(PageType $type): static
    {
        $this->pageType = $type;
        return $this;
    }

    public function extractFields(): array
    {
        return [];
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function render(): string
    {
        $parentId = $this->item?->get($this->parentKey);

        $rows = [];
        if ($parentId !== null && $parentId !== '' && class_exists($this->dataManagerClass)) {
            try {
                $params = ['filter' => [$this->foreignKey => $parentId]];
                if (!empty($this->order)) {
                    $params['order'] = $this->order;
                }
                if ($this->limit !== null) {
                    $params['limit'] = $this->limit;
                }
                $result = $this->dataManagerClass::getList($params);
                while ($row = $result->fetch()) {
                    $rows[] = $row;
                }
            } catch (Throwable) {
            }
        }

        $hasActions = $this->editUrlCallback !== null || $this->deleteUrlCallback !== null;
        $colCount = count($this->columns) + ($hasActions ? 1 : 0);
        $label = htmlspecialcharsbx($this->label);

        $addBtn = '';
        if ($this->createUrlCallback !== null && $parentId !== null) {
            $addUrl = htmlspecialcharsbx(($this->createUrlCallback)($parentId));
            $addBtn = ' <a href="' . $addUrl . '" class="ui-btn ui-btn-xs ui-btn-light-border adminkit-hasmany__add-btn">+ Добавить</a>';
        }

        ob_start();
        ?>
        <div class="adminkit-box adminkit-hasmany">
            <div class="adminkit-box__title adminkit-hasmany__header">
                <span class="adminkit-box__title-text"><?= $label ?></span>
                <?= $addBtn ?>
            </div>
            <div class="adminkit-hasmany__body">
                <?php
                if (empty($this->columns)): ?>
                    <div class="adminkit-hasmany__empty">Столбцы не заданы — используйте ->columns([...])</div>
                <?php else: ?>
                    <table class="adminkit-hasmany__table">
                        <thead>
                        <tr>
                            <?php
                            foreach ($this->columns as $col): ?>
                                <th><?= htmlspecialcharsbx($col->getLabel()) ?></th>
                            <?php
                            endforeach; ?>
                            <?php
                            if ($hasActions): ?>
                                <th></th><?php
                            endif; ?>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if (empty($rows)): ?>
                            <tr>
                                <td colspan="<?= $colCount ?>" class="adminkit-hasmany__empty">Нет записей</td>
                            </tr>
                        <?php else: ?>
                            <?php
                            foreach ($rows as $row): ?>
                                <tr>
                                    <?php
                                    foreach ($this->columns as $col): ?>
                                        <td><?= $this->renderCell($col, $row[$col->getColumn()] ?? null) ?></td>
                                    <?php
                                    endforeach; ?>
                                    <?php
                                    if ($hasActions): ?>
                                        <td class="adminkit-hasmany__actions">
                                            <?php
                                            if ($this->editUrlCallback): ?>
                                                <a href="<?= htmlspecialcharsbx(($this->editUrlCallback)($row)) ?>"
                                                   class="ui-btn ui-btn-xs ui-btn-light-border">Изменить</a>
                                            <?php
                                            endif; ?>
                                            <?php
                                            if ($this->deleteUrlCallback): ?>
                                                <a href="<?= htmlspecialcharsbx(($this->deleteUrlCallback)($row)) ?>"
                                                   class="ui-btn ui-btn-xs ui-btn-danger-light"
                                                   onclick="return confirm('Удалить запись?')">Удалить</a>
                                            <?php
                                            endif; ?>
                                        </td>
                                    <?php
                                    endif; ?>
                                </tr>
                            <?php
                            endforeach; ?>
                        <?php
                        endif; ?>
                        </tbody>
                    </table>
                <?php
                endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    protected function renderCell(FieldContract $field, mixed $value): string
    {
        if (method_exists($field, 'previewValue')) {
            return $field->previewValue($value);
        }
        if ($value === null || $value === '') {
            return '<span style="color:#aab5c0">—</span>';
        }
        return htmlspecialcharsbx((string)$value);
    }
}
