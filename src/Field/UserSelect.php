<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Main\UserTable;

class UserSelect extends DialogSelect
{
    public function __construct(string $label, ?string $column = null)
    {
        parent::__construct($label, $column);

        $this->entityId('user-list');
        $this->resolveLabels(static function (array $ids): array {
            if (!class_exists(UserTable::class)) {
                return [];
            }

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
