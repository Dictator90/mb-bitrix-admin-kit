<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Component\Renderers;

use MB\Bitrix\AdminKit\Component\ComponentContext;
use MB\Bitrix\AdminKit\Component\Renderers\ChildrenRenderer;
use MB\Bitrix\AdminKit\Contracts\UI\ComponentContract;
use MB\Bitrix\AdminKit\Contracts\UI\ConditionalVisibilityContract;
use MB\Bitrix\AdminKit\Contracts\UI\ItemAwareContract;
use MB\Bitrix\AdminKit\Contracts\UI\PageTypeAwareContract;
use MB\Bitrix\AdminKit\Field\Text;
use MB\Bitrix\AdminKit\Support\DataWrapper;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use PHPUnit\Framework\TestCase;

final class ChildrenRendererTest extends TestCase
{
    public function testRendersComponentsAndFieldsAndPropagatesContext(): void
    {
        $component = new class () implements ComponentContract, ItemAwareContract, PageTypeAwareContract, ConditionalVisibilityContract {
            private ?DataWrapper $item = null;
            private PageType $pageType = PageType::FORM;
            public function render(): string
            {
                return '<div data-page="' . $this->pageType->value . '" data-item="' . (string)$this->item?->get('ID') . '">cmp</div>';
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
            public function visibleWhen(string $column, mixed $value): static
            {
                return $this;
            }
            public function getVisibleWhen(): ?array
            {
                return null;
            }
        };

        $field = Text::make('Name', 'NAME');
        $item = DataWrapper::fromArray(['ID' => 7, 'NAME' => 'John']);
        $html = (new ChildrenRenderer())->render([$component, $field], new ComponentContext($item, PageType::FORM));

        self::assertStringContainsString('data-page="form"', $html);
        self::assertStringContainsString('data-item="7"', $html);
        self::assertStringContainsString('ui-form-row', $html);
        self::assertStringContainsString('data-field-column="NAME"', $html);
    }
}
