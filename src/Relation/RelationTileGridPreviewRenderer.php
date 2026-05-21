<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Relation;

use MB\Bitrix\AdminKit\Support\AdminString;

/**
 * Renders HasMany preview via Bitrix ui.tilegrid ({@see BX.TileGrid.Grid}).
 */
final class RelationTileGridPreviewRenderer
{
    private const  ROW_HEIGHT = 40;

    /**
     * @param list<array<string, mixed>> $rows
     * @param array<string, string>|null $columnDefinitions column => label; null = auto-detect from row keys
     */
    public function render(array $rows, string $columnPrefix, ?array $columnDefinitions = null): string
    {
        if ($rows === []) {
            return '<span class="adminkit-relation-preview">—</span>';
        }

        $columns = self::buildColumnDefinitions($columnDefinitions, $rows);
        $columnIds = array_map(static fn (array $column): string => $column['id'], $columns);
        $rows = self::projectRows($rows, $columnIds);
        $containerId = AdminString::htmlId('adminkit-relation-tilegrid', $columnPrefix);
        $gridId = $containerId . '_grid';

        $config = [
            'containerId' => $containerId,
            'gridId' => $gridId,
            'columns' => $columns,
            'rows' => $rows,
            'itemHeight' => self::ROW_HEIGHT,
        ];

        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return '<span class="adminkit-relation-preview">—</span>';
        }

        $containerIdEsc = htmlspecialcharsbx($containerId);

        return '<div id="'
            . $containerIdEsc
            . '" class="adminkit-relation-tilegrid"></div>'
            . '<script>BX.ready(function(){var cfg='
            . $json
            . ';var run=function(){if(window.MB&&MB.AdminKit&&MB.AdminKit.RelationTileGrid){MB.AdminKit.RelationTileGrid.init(cfg);}};'
            . 'if(BX.Runtime&&BX.Runtime.loadExtension){'
            . 'Promise.all([BX.Runtime.loadExtension("ui.tilegrid"),BX.Runtime.loadExtension("mb.admin.kit")]).then(run).catch(run);'
            . '}else{run();}});</script>';
    }

    /**
     * @param array<string, string>|null $columnDefinitions
     * @param list<array<string, mixed>> $rows
     * @return list<array{id: string, label: string}>
     */
    public static function buildColumnDefinitions(?array $columnDefinitions, array $rows): array
    {
        if ($columnDefinitions !== null && $columnDefinitions !== []) {
            $columns = [];
            foreach ($columnDefinitions as $id => $label) {
                if (!is_string($id) || $id === '') {
                    continue;
                }

                $columns[] = [
                    'id' => $id,
                    'label' => RelationOrmFieldLabelResolver::normalizeTitle($label, $id),
                ];
            }

            return $columns;
        }

        $ids = self::resolveColumnIds($rows);

        return array_map(
            static fn (string $id): array => ['id' => $id, 'label' => $id],
            $ids,
        );
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<string>
     */
    public static function resolveColumnIds(array $rows): array
    {
        $columns = [];

        foreach ($rows as $row) {
            foreach (array_keys($row) as $key) {
                if (!is_string($key) || $key === '') {
                    continue;
                }

                if (!in_array($key, $columns, true)) {
                    $columns[] = $key;
                }
            }
        }

        usort($columns, static function (string $a, string $b): int {
            if ($a === 'ID') {
                return -1;
            }

            if ($b === 'ID') {
                return 1;
            }

            return strcmp($a, $b);
        });

        return $columns;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param list<string> $columnIds
     * @return list<array<string, mixed>>
     */
    private static function projectRows(array $rows, array $columnIds): array
    {
        if ($columnIds === []) {
            return $rows;
        }

        $projected = [];
        foreach ($rows as $row) {
            $item = [];
            foreach ($columnIds as $columnId) {
                if (array_key_exists($columnId, $row)) {
                    $item[$columnId] = $row[$columnId];
                }
            }

            $projected[] = $item;
        }

        return $projected;
    }
}
