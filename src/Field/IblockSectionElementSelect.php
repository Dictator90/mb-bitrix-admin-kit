<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Field;

use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;

/**
 * Выбор услуг для главной: один диалог ui.entity-selector с двумя вкладками —
 * «Разделы» и «Элементы» выбранного инфоблока. В отличие от {@see IblockElementSelect}
 * позволяет смешивать разделы и элементы в одном упорядоченном (drag&drop) списке.
 *
 * Значения хранятся с префиксом типа, поэтому раздел и элемент с одинаковым ID
 * не конфликтуют и тип восстанавливается без обращения к БД:
 *   s:<sectionId>   — раздел
 *   e:<elementId>   — элемент
 * Голое число трактуется как элемент (обратная совместимость со старым форматом).
 *
 * Список сущностей грузится статически (по одному запросу на разделы и элементы) —
 * подходит для небольших инфоблоков (услуги). Для крупных справочников нужен
 * динамический провайдер.
 */
class IblockSectionElementSelect extends DialogSelect
{
    protected const ENTITY_ID = 'mbSectionElement';
    protected const TAB_SECTIONS = 'sections';
    protected const TAB_ELEMENTS = 'elements';

    protected int $iblockId = 0;
    protected string $sectionsTabTitle = 'Разделы';
    protected string $elementsTabTitle = 'Элементы';

    public function __construct(?string $label = null, ?string $column = null, int $iblockId = 0)
    {
        parent::__construct($label, $column);
        $this->multiple();
        $this->sortable();
        $this->entityId(self::ENTITY_ID);
        $this->iblockId($iblockId);
    }

    public static function make(?string $label = null, ?string $column = null, int $iblockId = 0): static
    {
        return new static($label, $column, $iblockId);
    }

    public function tabsTitles(string $sections, string $elements): static
    {
        $this->sectionsTabTitle = $sections;
        $this->elementsTabTitle = $elements;

        return $this->iblockId($this->iblockId);
    }

    public function iblockId(int $iblockId): static
    {
        $this->iblockId = $iblockId;
        $this->rebuild();

        return $this;
    }

    /**
     * Раскладывает сохранённое значение поля в упорядоченный типизированный список.
     *
     * @return array<int, array{type: 'section'|'element', id: int}>
     */
    public static function decode(mixed $value): array
    {
        if (is_string($value)) {
            $value = $value !== '' ? preg_split('/\s*,\s*/', $value) : [];
        }
        if (!is_array($value)) {
            $value = $value === null || $value === '' ? [] : [$value];
        }

        $items = [];
        foreach ($value as $raw) {
            $raw = trim((string)$raw);
            if ($raw === '') {
                continue;
            }

            if (str_starts_with($raw, 's:')) {
                $id = (int)substr($raw, 2);
                if ($id > 0) {
                    $items[] = ['type' => 'section', 'id' => $id];
                }
            } elseif (str_starts_with($raw, 'e:')) {
                $id = (int)substr($raw, 2);
                if ($id > 0) {
                    $items[] = ['type' => 'element', 'id' => $id];
                }
            } elseif (ctype_digit($raw)) {
                $items[] = ['type' => 'element', 'id' => (int)$raw];
            }
        }

        return $items;
    }

    protected function rebuild(): void
    {
        [$sections, $elements] = $this->loadOptions();

        $this->items([]);
        $this->tabs([]);
        $this->tabsContent([
            self::TAB_SECTIONS => [
                'title' => $this->sectionsTabTitle,
                'items' => $sections,
            ],
            self::TAB_ELEMENTS => [
                'title' => $this->elementsTabTitle,
                'items' => $elements,
            ],
        ]);

        $iblockId = $this->iblockId;
        $this->resolveLabels(static fn (array $ids): array => self::resolveLabelsFor($ids, $iblockId));
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    protected function loadOptions(): array
    {
        if ($this->iblockId <= 0 || !Loader::includeModule('iblock')) {
            return [[], []];
        }

        $sections = [];
        $sectionRows = SectionTable::query()
            ->setSelect(['ID', 'NAME'])
            ->where('IBLOCK_ID', $this->iblockId)
            ->setOrder(['LEFT_MARGIN' => 'ASC'])
            ->fetchAll();
        foreach ($sectionRows as $row) {
            $sections[] = [
                'id' => 's:' . $row['ID'],
                'entityId' => self::ENTITY_ID,
                'title' => (string)($row['NAME'] ?: $row['ID']),
                'tabs' => [self::TAB_SECTIONS],
            ];
        }

        $elements = [];
        $elementRows = ElementTable::query()
            ->setSelect(['ID', 'NAME'])
            ->where('IBLOCK_ID', $this->iblockId)
            ->where('ACTIVE', 'Y')
            ->setOrder(['SORT' => 'ASC', 'NAME' => 'ASC'])
            ->fetchAll();
        foreach ($elementRows as $row) {
            $elements[] = [
                'id' => 'e:' . $row['ID'],
                'entityId' => self::ENTITY_ID,
                'title' => (string)($row['NAME'] ?: $row['ID']),
                'tabs' => [self::TAB_ELEMENTS],
            ];
        }

        return [$sections, $elements];
    }

    /**
     * @param string[] $ids
     * @return array<string, string>
     */
    protected static function resolveLabelsFor(array $ids, int $iblockId): array
    {
        if ($ids === [] || $iblockId <= 0 || !Loader::includeModule('iblock')) {
            return [];
        }

        $sectionIds = [];
        $elementIds = [];
        foreach ($ids as $raw) {
            foreach (self::decode($raw) as $item) {
                if ($item['type'] === 'section') {
                    $sectionIds[$item['id']] = 's:' . $item['id'];
                } else {
                    $elementIds[$item['id']] = 'e:' . $item['id'];
                }
            }
        }

        $titles = [];
        if ($sectionIds !== []) {
            $rows = SectionTable::query()
                ->setSelect(['ID', 'NAME'])
                ->where('IBLOCK_ID', $iblockId)
                ->whereIn('ID', array_keys($sectionIds))
                ->fetchAll();
            foreach ($rows as $row) {
                $titles['s:' . $row['ID']] = (string)($row['NAME'] ?: $row['ID']);
            }
        }
        if ($elementIds !== []) {
            $rows = ElementTable::query()
                ->setSelect(['ID', 'NAME'])
                ->where('IBLOCK_ID', $iblockId)
                ->whereIn('ID', array_keys($elementIds))
                ->fetchAll();
            foreach ($rows as $row) {
                $key = 'e:' . $row['ID'];
                $titles[$key] = (string)($row['NAME'] ?: $row['ID']);
                $titles[(string)$row['ID']] = $titles[$key];
            }
        }

        return $titles;
    }
}
