<?php

namespace LynkByte\DocsBuilder\Data;

/**
 * Represents a documentation page with its navigation metadata.
 *
 * Implements \ArrayAccess for backward compatibility with code that
 * accesses page data using array syntax (e.g. $page['slug']).
 *
 * @implements \ArrayAccess<string, mixed>
 */
final class Page implements \ArrayAccess
{
    public function __construct(
        public readonly string $title,
        public readonly string $file,
        public readonly string $slug,
        public readonly string $url,
        public readonly ?string $icon = null,
        public readonly string $layout = 'documentation',
    ) {}

    /**
     * Convert the page to an associative array.
     *
     * @return array{title: string, file: string, slug: string, url: string, icon: ?string, layout: string}
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'file' => $this->file,
            'slug' => $this->slug,
            'url' => $this->url,
            'icon' => $this->icon,
            'layout' => $this->layout,
        ];
    }

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('Page is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('Page is immutable.');
    }
}
