<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Component\Relation;

use Throwable;

/**
 * Renders the fields of a single related record inline.
 * The related record is found using a foreign key on the related table.
 *
 * Usage:
 *   HasOne::make('Профиль', ProfileTable::class)
 *       ->foreignKey('USER_ID')
 *       ->columns([
 *           Text::make('Телефон', 'PHONE'),
 *           Text::make('Город', 'CITY'),
 *       ])
 *       ->editUrl(fn($row) => '/bitrix/admin/profile_edit.php?id=' . $row['ID'])
 */
class HasOne extends HasMany
{
    public static function make(string $label, string $dataManagerClass): static
    {
        return new static($label, $dataManagerClass);
    }

    public function render(): string
    {
        $parentId = $this->item?->get($this->parentKey);
        $row = null;

        if ($parentId !== null && $parentId !== '' && class_exists($this->dataManagerClass)) {
            try {
                $params = [
                        'filter' => [$this->foreignKey => $parentId],
                        'limit' => 1,
                ];
                $row = $this->dataManagerClass::getList($params)->fetch() ?: null;
            } catch (Throwable) {
            }
        }

        $label = htmlspecialcharsbx($this->label);
        $editBtn = '';
        if ($this->editUrlCallback !== null && $row !== null) {
            $editUrl = htmlspecialcharsbx(($this->editUrlCallback)($row));
            $editBtn = ' <a href="' . $editUrl . '" class="ui-btn ui-btn-xs ui-btn-light-border">Изменить</a>';
        }
        if ($this->createUrlCallback !== null && $row === null && $parentId !== null) {
            $createUrl = htmlspecialcharsbx(($this->createUrlCallback)($parentId));
            $editBtn = ' <a href="' . $createUrl . '" class="ui-btn ui-btn-xs ui-btn-success-light">Создать</a>';
        }

        ob_start();
        ?>
        <div class="adminkit-box adminkit-hasone">
            <div class="adminkit-box__title adminkit-hasmany__header">
                <span class="adminkit-box__title-text"><?= $label ?></span>
                <?= $editBtn ?>
            </div>
            <div class="adminkit-hasmany__body">
                <?php
                if ($row === null): ?>
                    <div class="adminkit-hasmany__empty">Запись не найдена</div>
                <?php
                elseif (empty($this->columns)): ?>
                    <div class="adminkit-hasmany__empty">Столбцы не заданы — используйте ->columns([...])</div>
                <?php
                else: ?>
                    <div class="ui-form">
                        <?php
                        foreach ($this->columns as $col): ?>
                            <div class="ui-form-row">
                                <div class="ui-form-label">
                                    <div class="ui-ctl-label-text"><?= htmlspecialcharsbx($col->getLabel()) ?></div>
                                </div>
                                <div class="ui-form-content">
                                    <?= $this->renderCell($col, $row[$col->getColumn()] ?? null) ?>
                                </div>
                            </div>
                        <?php
                        endforeach; ?>
                    </div>
                <?php
                endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
