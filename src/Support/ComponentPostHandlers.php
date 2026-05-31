<?php

declare(strict_types=1);

namespace MB\Bitrix\AdminKit\Support;

use MB\Bitrix\AdminKit\Component\Layout\Tab;
use MB\Bitrix\AdminKit\Component\Layout\Tabs;
use MB\Bitrix\AdminKit\Contracts\UI\PostHandlerContract;
use Throwable;

/**
 * Recursively walks a page's components and invokes `handleFormPost()` on
 * every component that implements `PostHandlerContract`.
 *
 * Walks into Tabs (skipping hidden tabs), Tab items, and any container
 * exposing `getChildren()` (the standard layout components — Box, Grid,
 * Flex, Column, etc.).
 *
 * One handler's exception is captured and forwarded via the optional
 * $errorSink so a failing rights save can't silently abort the rest of
 * the POST lifecycle.
 */
final class ComponentPostHandlers
{
    /**
     * @param iterable<mixed> $items
     * @param callable(string):void|null $errorSink Receives each thrown error message; null = swallow.
     */
    public static function runAll(iterable $items, ?callable $errorSink = null): void
    {
        foreach (self::collect($items) as $handler) {
            try {
                $handler->handleFormPost();
            } catch (Throwable $exception) {
                if ($errorSink !== null) {
                    $errorSink($exception->getMessage());
                }
            }
        }
    }

    /**
     * @param iterable<mixed> $items
     * @return list<PostHandlerContract>
     */
    public static function collect(iterable $items): array
    {
        $result = [];

        foreach ($items as $item) {
            if ($item instanceof PostHandlerContract) {
                $result[] = $item;
            }

            if ($item instanceof Tabs) {
                foreach ($item->getTabs() as $tab) {
                    if (!$tab->isVisible()) {
                        continue;
                    }
                    $result = array_merge($result, self::collect($tab->getItems()));
                }
                continue;
            }

            if ($item instanceof Tab) {
                if ($item->isVisible()) {
                    $result = array_merge($result, self::collect($item->getItems()));
                }
                continue;
            }

            if (is_object($item) && method_exists($item, 'getChildren')) {
                /** @var iterable<mixed> $children */
                $children = $item->getChildren();
                $result = array_merge($result, self::collect($children));
            }
        }

        return $result;
    }
}
