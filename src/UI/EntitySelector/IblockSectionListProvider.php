<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\UI\EntitySelector;

use Bitrix\Iblock\Component\Tools;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\UI\EntitySelector\BaseProvider;
use Bitrix\UI\EntitySelector\Dialog;
use Bitrix\UI\EntitySelector\Item;
use Bitrix\UI\EntitySelector\SearchQuery;

class IblockSectionListProvider extends BaseProvider
{
    public const ENTITY_ID = 'iblock-section-list';
    private const SECTIONS_LIMIT = 100;

    /**
     * @param array<string, mixed> $options
     *
     * @throws LoaderException
     */
    public function __construct(array $options = [])
    {
        Loader::includeModule('iblock');
        parent::__construct();

        if (empty($options['selected'])) {
            $options['selected'] = [];
        } elseif (!is_array($options['selected'])) {
            $options['selected'] = [$options['selected']];
        }

        $this->options = $options;
    }

    public function isAvailable(): bool
    {
        global $USER;

        return is_object($USER) && $USER->isAuthorized();
    }

    /**
     * @param array<int|string> $ids
     *
     * @return Item[]
     */
    public function getItems(array $ids): array
    {
        $items = [];
        $filter = $ids !== [] ? ['ID' => $ids] : [];

        foreach ($this->getSections($filter) as $section) {
            $items[] = $this->makeItem($section);
        }

        return $items;
    }

    public function fillDialog(Dialog $dialog): void
    {
        $dialog->loadPreselectedItems();

        if ($dialog->getItemCollection()->count() > 0) {
            foreach ($dialog->getItemCollection() as $item) {
                $dialog->addRecentItem($item);
            }
        }

        $recentItems = $dialog->getRecentItems()->getEntityItems(self::ENTITY_ID);
        if (count($recentItems) < self::SECTIONS_LIMIT) {
            foreach ($this->getSections([], self::SECTIONS_LIMIT) as $section) {
                $dialog->addRecentItem($this->makeItem($section));
            }
        }
    }

    public function doSearch(SearchQuery $searchQuery, Dialog $dialog): void
    {
        $filter = [];
        $query = $searchQuery->getQuery();
        if ($query !== '') {
            $filter = $this->getQueryFilter($query);
        }

        $sections = $this->getSections($filter, self::SECTIONS_LIMIT);
        if (count($sections) === self::SECTIONS_LIMIT) {
            $searchQuery->setCacheable(false);
        }

        foreach ($sections as $section) {
            $dialog->addItem($this->makeItem($section));
        }
    }

    /**
     * @param array<int|string> $ids
     *
     * @return Item[]
     */
    public function getPreselectedItems(array $ids): array
    {
        return $this->getItems($ids);
    }

    /**
     * @return array<string, mixed>
     */
    private function getQueryFilter(string $query): array
    {
        return [
            '%NAME' => $query,
        ];
    }

    /**
     * @param array<string, mixed> $additionalFilter
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getSections(array $additionalFilter = [], ?int $limit = null): array
    {
        $sections = [];
        $filter = $this->getDefaultFilter();
        if ($additionalFilter !== []) {
            $filter = array_merge($filter, $additionalFilter);
        }

        $navParams = false;
        if ($limit !== null) {
            $navParams = ['nTopCount' => $limit];
        }

        $selectFields = [
            'ID',
            'NAME',
            'DESCRIPTION',
            'PICTURE',
            'IBLOCK_ID',
            'XML_ID',
            'DEPTH_LEVEL',
        ];

        $sectionData = \CIBlockSection::GetList(
            ['LEFT_MARGIN' => 'ASC'],
            $filter,
            false,
            $selectFields,
            $navParams,
        );

        while ($section = $sectionData->fetch()) {
            $section['PICTURE'] = $this->getImageSource((int)($section['PICTURE'] ?? 0));
            $sections[] = $section;
        }

        return $sections;
    }

    /**
     * @param array<string, mixed> $section
     */
    protected function makeItem(array $section): Item
    {
        $selected = is_array($this->options['selected'] ?? null) ? $this->options['selected'] : [];

        return new Item([
            'id' => $section['ID'] ?? null,
            'entityId' => self::ENTITY_ID,
            'title' => $section['NAME'] ?? null,
            'subtitle' => $section['ID'] ?? null,
            'description' => $section['DESCRIPTION'] ?? null,
            'avatar' => $section['PICTURE'] ?? null,
            'selected' => in_array($section['ID'], $selected, false),
            'customData' => [
                'xmlId' => $section['XML_ID'] ?? null,
                'iblockId' => $section['IBLOCK_ID'] ?? null,
                'depthLevel' => $section['DEPTH_LEVEL'] ?? null,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultFilter(): array
    {
        $filter = [
            'CHECK_PERMISSIONS' => 'Y',
            'MIN_PERMISSION' => 'R',
        ];

        $iblockId = (int)$this->getOption('iblockId', 0);
        if ($iblockId > 0) {
            $filter['IBLOCK_ID'] = $iblockId;
        }

        if ($this->getOption('activeFilter', false)) {
            $filter['ACTIVE'] = 'Y';
        }

        return $filter;
    }

    private function getImageSource(int $id): ?string
    {
        if ($id <= 0) {
            return null;
        }

        $file = \CFile::GetFileArray($id);
        if (!$file) {
            return null;
        }

        return Tools::getImageSrc($file, false) ?: null;
    }
}
