<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Tests\Page;

use MB\Bitrix\AdminKit\Page\Crud\DetailPage;
use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Tests\Fixtures\ProductResource;
use PHPUnit\Framework\TestCase;

final class CustomIndexPageWithoutFieldsOverrideUsesResourceIndexFieldsTest extends TestCase
{
    public function testCustomIndexPageWithoutFieldsOverrideUsesResourceIndexFields(): void
    {
        $page = new CustomIndexPageWithoutFieldsOverride(new ProductResource());

        self::assertSame(['ID', 'NAME'], $page->fieldColumns());
    }

    public function testCustomFormPageWithoutFieldsOverrideUsesResourceFormFields(): void
    {
        $page = new CustomFormPageWithoutFieldsOverride(new ProductResource());

        self::assertSame(['NAME'], $page->fieldColumns());
    }

    public function testCustomDetailPageWithoutFieldsOverrideUsesResourceDetailFields(): void
    {
        $page = new CustomDetailPageWithoutFieldsOverride(new ProductResource());

        self::assertSame(['NAME'], $page->fieldColumns());
    }

    public function testStringPrimaryKeyIsPassedToResourcePages(): void
    {
        $resource = new ProductResource();

        self::assertSame('sku-42', TestStringIdFormPage::fromResource($resource, 'sku-42')->idValue());
        self::assertSame('sku-42', TestStringIdDetailPage::fromResource($resource, 'sku-42')->idValue());
    }
}

final class CustomIndexPageWithoutFieldsOverride extends IndexPage
{
    public function fieldColumns(): array
    {
        return array_map(static fn ($field): string => $field->getColumn(), iterator_to_array($this->fields()));
    }
}

final class CustomFormPageWithoutFieldsOverride extends FormPage
{
    public function fieldColumns(): array
    {
        return array_map(static fn ($field): string => $field->getColumn(), iterator_to_array($this->fields()));
    }
}

final class CustomDetailPageWithoutFieldsOverride extends DetailPage
{
    public function fieldColumns(): array
    {
        return array_map(static fn ($field): string => $field->getColumn(), iterator_to_array($this->fields()));
    }
}

final class TestStringIdFormPage extends FormPage
{
    public static function fromResource(ProductResource $resource, mixed $id): self
    {
        return new self($resource, $id, ['mode' => 'edit']);
    }

    public function idValue(): mixed
    {
        return $this->id;
    }
}

final class TestStringIdDetailPage extends DetailPage
{
    public static function fromResource(ProductResource $resource, mixed $id): self
    {
        return new self($resource, $id);
    }

    public function idValue(): mixed
    {
        return $this->id;
    }
}
