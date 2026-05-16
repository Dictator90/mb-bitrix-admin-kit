<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Resource\Concerns;

use MB\Bitrix\AdminKit\Page\DetailPage;
use MB\Bitrix\AdminKit\Page\FormPage;
use MB\Bitrix\AdminKit\Page\IndexPage;
use MB\Bitrix\AdminKit\Page\ResourcePageResolver;

trait HasResourcePages
{
    /** @return iterable<class-string<\MB\Bitrix\AdminKit\Contracts\PageContract>> */
    public function pages(): iterable
    {
        return [
            IndexPage::class,
            FormPage::class,
            DetailPage::class,
        ];
    }

    public function indexPage(): IndexPage
    {
        $page = (new ResourcePageResolver())->resolve($this, IndexPage::pageName());

        if (!$page instanceof IndexPage) {
            throw new \LogicException('The index page must extend ' . IndexPage::class . '.');
        }

        return $page;
    }

    public function formPage(mixed $id = null): FormPage
    {
        $page = (new ResourcePageResolver())->resolve(
            $this,
            FormPage::pageName(),
            $id,
            ['mode' => $id === null ? 'create' : 'edit'],
        );

        if (!$page instanceof FormPage) {
            throw new \LogicException('The form page must extend ' . FormPage::class . '.');
        }

        return $page;
    }

    public function detailPage(mixed $id): DetailPage
    {
        $page = (new ResourcePageResolver())->resolve($this, DetailPage::pageName(), $id);

        if (!$page instanceof DetailPage) {
            throw new \LogicException('The detail page must extend ' . DetailPage::class . '.');
        }

        return $page;
    }
}
