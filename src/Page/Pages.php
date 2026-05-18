<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Page;

use Countable;
use IteratorAggregate;
use LogicException;
use MB\Bitrix\AdminKit\Contracts\Page\PageContract as CorePageContract;
use MB\Bitrix\AdminKit\Contracts\Page\PagesContract;
use MB\Bitrix\AdminKit\Contracts\Page\ResourcePageContract;
use MB\Bitrix\AdminKit\Contracts\ResourceContract;
use MB\Bitrix\AdminKit\Support\Enums\PageType;
use ReflectionClass;
use RuntimeException;
use Traversable;

/** @implements IteratorAggregate<int, CorePageContract> */
final class Pages implements Countable, IteratorAggregate, PagesContract
{
    /** @var array<int, class-string<CorePageContract>|CorePageContract> */
    private array $entries = [];

    /** @var array<string, class-string<CorePageContract>|CorePageContract> */
    private array $byName = [];

    private ?ResourceContract $resource = null;

    public function __construct(iterable $pages = [])
    {
        foreach ($pages as $page) {
            $this->register($page);
        }
    }

    /** @param iterable<class-string<CorePageContract>|CorePageContract> $pages */
    public static function make(iterable $pages): self
    {
        $collection = new self();
        foreach ($pages as $page) {
            $collection->register($page);
        }

        return $collection;
    }

    public function setResource(ResourceContract $resource): self
    {
        $this->resource = $resource;

        foreach ($this->entries as $index => $entry) {
            if (is_string($entry)) {
                continue;
            }

            if ($entry instanceof ResourcePageContract) {
                $entry->setResource($resource);
            }

            $this->entries[$index] = $entry;
        }

        return $this;
    }

    public function findByName(string $name): ?CorePageContract
    {
        $entry = $this->byName[$name] ?? null;

        return $entry === null ? null : $this->resolve($entry);
    }

    public function findByType(PageType $type): ?CorePageContract
    {
        foreach ($this->entries as $entry) {
            $page = $this->resolve($entry);
            if ($page->pageType() === $type) {
                return $page;
            }
        }

        return null;
    }

    public function findByClass(string $class): ?CorePageContract
    {
        foreach ($this->entries as $entry) {
            $page = $this->resolve($entry);
            if ($page instanceof $class) {
                return $page;
            }
        }

        return null;
    }

    public function indexPage(): ?CorePageContract
    {
        return $this->findByType(PageType::INDEX) ?? $this->findByName('index');
    }

    public function formPage(): ?CorePageContract
    {
        return $this->findByType(PageType::FORM) ?? $this->findByName('form');
    }

    public function detailPage(): ?CorePageContract
    {
        return $this->findByType(PageType::DETAIL) ?? $this->findByName('detail');
    }

    public function activePage(): ?CorePageContract
    {
        $pageName = (string)($_REQUEST['page'] ?? $_GET['page'] ?? '');

        return $pageName !== '' ? $this->findByName($pageName) : null;
    }

    /** @return array<int, CorePageContract> */
    public function all(): array
    {
        return array_map(fn (string|CorePageContract $entry): CorePageContract => $this->resolve($entry), $this->entries);
    }

    public function getIterator(): Traversable
    {
        foreach ($this->entries as $entry) {
            yield $this->resolve($entry);
        }
    }

    public function count(): int
    {
        return count($this->entries);
    }

    private function register(mixed $page): void
    {
        if (!is_string($page) && !$page instanceof CorePageContract) {
            throw new LogicException(sprintf(
                'Resource %s pages() must return class name strings or page objects, got %s.',
                $this->resource !== null ? $this->resource::class : 'unknown',
                get_debug_type($page),
            ));
        }

        if (is_string($page)) {
            $this->assertPageClass($page);
            $name = $page::pageName();
            $this->store($name, $page);

            return;
        }

        if (!$page instanceof CorePageContract) {
            throw new LogicException('Page must implement ' . CorePageContract::class . '.');
        }

        $this->store($page::pageName(), $page);
    }

    private function store(string $name, string|CorePageContract $page): void
    {
        if (isset($this->byName[$name])) {
            throw new LogicException(sprintf(
                'Duplicate page name "%s": %s and %s.',
                $name,
                is_string($page) ? $page : $page::class,
                is_string($this->byName[$name]) ? $this->byName[$name] : $this->byName[$name]::class,
            ));
        }

        $this->byName[$name] = $page;
        $this->entries[] = $page;
    }

    /** @param class-string<CorePageContract> $pageClass */
    private function assertPageClass(string $pageClass): void
    {
        if (!class_exists($pageClass)) {
            throw new RuntimeException(sprintf('Page class %s does not exist.', $pageClass));
        }

        if (!is_subclass_of($pageClass, CorePageContract::class)) {
            throw new RuntimeException(sprintf('Page class %s must implement %s.', $pageClass, CorePageContract::class));
        }

        $reflection = new ReflectionClass($pageClass);
        if ($reflection->isAbstract()) {
            throw new RuntimeException(sprintf('Page class %s must not be abstract.', $pageClass));
        }
    }

    private function resolve(string|CorePageContract $entry): CorePageContract
    {
        if (!is_string($entry)) {
            return $entry;
        }

        $page = (new PageFactory())->make($entry, $this->resource);
        if ($this->resource !== null && $page instanceof ResourcePageContract) {
            $page->setResource($this->resource);
        }

        return $page;
    }
}
