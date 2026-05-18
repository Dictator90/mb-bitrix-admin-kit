<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use Bitrix\Main\Context;
use MB\Bitrix\AdminKit\Contracts\Page\PageContract as CorePageContract;
use MB\Bitrix\AdminKit\Page\Concerns\HasPageAssets;
use MB\Bitrix\AdminKit\Page\Concerns\HasPageBreadcrumbs;
use MB\Bitrix\AdminKit\Page\Concerns\HasPageComponents;
use MB\Bitrix\AdminKit\Page\Concerns\HasPageIdentity;
use MB\Bitrix\AdminKit\Page\Concerns\HasPageRequest;
use MB\Bitrix\AdminKit\Page\Concerns\HasPageResponse;
use MB\Bitrix\AdminKit\Page\Concerns\HasPageToolbar;

abstract class Page implements CorePageContract
{
    use HasPageAssets;
    use HasPageBreadcrumbs;
    use HasPageComponents;
    use HasPageIdentity;
    use HasPageRequest;
    use HasPageResponse;
    use HasPageToolbar;

    /** @var array<string,mixed> */
    protected array $params = [];

    /** @param array<string,mixed> $params */
    public function __construct(array $params = [])
    {
        $this->params = $params;
        $this->request = Context::getCurrent()->getRequest();
    }

    abstract public function render(): void;
}
