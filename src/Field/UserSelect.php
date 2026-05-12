<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\UserTable;

/**
 * User entity selector — wraps the 'user-list' entity from mb.ui.dialog-selector.
 *
 *   UserSelect::make('Менеджер', 'MANAGER_ID')
 *   UserSelect::make('Исполнители', 'ASSIGNEE_IDS')->multiple()
 */
class UserSelect extends EntitySelect
{
    public function __construct(string $label, ?string $column = null)
    {
        parent::__construct($label, $column);

        $this->entity('user-list');

        $this->resolveLabels(static function (array $ids): array {
            $result = [];
            $rs = UserTable::getList([
                'filter' => ['@ID' => $ids],
                'select' => ['ID', 'NAME', 'LAST_NAME', 'LOGIN'],
            ]);
            while ($row = $rs->fetch()) {
                $display = trim(($row['NAME'] ?? '') . ' ' . ($row['LAST_NAME'] ?? ''));
                $result[(string)$row['ID']] = $display !== '' ? $display : ($row['LOGIN'] ?? (string)$row['ID']);
            }

            return $result;
        });
    }
}
