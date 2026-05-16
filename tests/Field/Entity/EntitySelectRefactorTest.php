<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Field\Entity;

use MB\Bitrix\AdminKit\Field\Entity\EntitySelectorProviderResolver;
use MB\Bitrix\AdminKit\Field\Entity\EntitySelectorValueNormalizer;
use MB\Bitrix\AdminKit\Field\Entity\Renderers\TagSelectorRenderer;
use MB\Bitrix\AdminKit\Field\EntitySelect;
use PHPUnit\Framework\TestCase;

final class EntitySelectRefactorTest extends TestCase
{
    public function testNormalizeAndSerializeSupportScalarArrayAndCommaString(): void
    {
        $field = EntitySelect::make('User', 'USER_ID')->multiple();

        self::assertSame(['1', '2'], $field->normalize(['1', '2']));
        self::assertSame(['1', '2'], (new EntitySelectorValueNormalizer())->parseIds('1,2'));
        self::assertSame('1,2', $field->serializePostValue(['1', '2']));
    }

    public function testProviderResolverMapsKnownEntityIds(): void
    {
        $resolver = new EntitySelectorProviderResolver();
        self::assertNotNull($resolver->resolveProviderClass('user'));
        self::assertNull($resolver->resolveProviderClass('unknown'));
    }

    public function testTagRendererProducesHiddenInputsAndPreviewRendersChips(): void
    {
        $renderer = new TagSelectorRenderer();
        $hidden = $renderer->renderHiddenInputs(['1', '2'], 'USER_ID[]', 'USER_ID', true);
        self::assertStringContainsString('name="USER_ID[]"', $hidden);

        $field = EntitySelect::make('User', 'USER_ID')->resolveLabels(static fn (array $ids): array => array_combine($ids, $ids));
        $chips = $field->previewValue(['1', '2']);
        self::assertStringContainsString('adminkit-entity-selector__chip', $chips);
    }
}
