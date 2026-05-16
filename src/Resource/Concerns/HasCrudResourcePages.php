<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

use MB\Bitrix\AdminKit\Contracts\Page\ResourcePageContract;
use MB\Bitrix\AdminKit\Page\Crud\DetailPage;
use MB\Bitrix\AdminKit\Page\Crud\FormPage;
use MB\Bitrix\AdminKit\Page\Crud\IndexPage;
use MB\Bitrix\AdminKit\Page\Pages;
use MB\Bitrix\AdminKit\Page\ResourcePageResolver;

trait HasCrudResourcePages
{
    /** @return iterable<class-string<ResourcePageContract>> */
    public function pages(): iterable
    {
        return [
            IndexPage::class,
            FormPage::class,
            DetailPage::class,
        ];
    }

    public function getPages(): Pages
    {
        return Pages::make($this->pages())->setResource($this);
    }

    public function indexPage(): IndexPage
    {
        return $this->resolveCrudPage(IndexPage::class, IndexPage::pageName());
    }

    public function formPage(mixed $id = null): FormPage
    {
        return $this->resolveCrudPage(
            FormPage::class,
            FormPage::pageName(),
            $id,
            ['mode' => $id === null ? 'create' : 'edit'],
        );
    }

    public function detailPage(mixed $id): DetailPage
    {
        return $this->resolveCrudPage(DetailPage::class, DetailPage::pageName(), $id);
    }

    /**
     * @template T of IndexPage|FormPage|DetailPage
     * @param class-string<T> $expectedClass
     * @param array<string,mixed> $params
     * @return T
     */
    private function resolveCrudPage(string $expectedClass, string $pageName, mixed $id = null, array $params = []): IndexPage|FormPage|DetailPage
    {
        $page = (new ResourcePageResolver())->resolve($this, $pageName, $id, $params);

        if (!$page instanceof $expectedClass) {
            throw new \LogicException(sprintf('The %s page must extend %s.', $pageName, $expectedClass));
        }

        return $page;
    }
}
