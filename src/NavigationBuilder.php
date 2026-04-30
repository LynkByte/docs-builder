<?php

namespace LynkByte\DocsBuilder;

use LynkByte\DocsBuilder\Data\Page;

/**
 * @phpstan-type NavigationSection array{title: string, pages: array<int, Page>}
 * @phpstan-type PrevNextPage array{title: string, url: string}
 */
class NavigationBuilder
{
    private string $baseUrl;

    /** @var array<string, mixed> */
    private array $config;

    /** @var array<int, Page> */
    private array $flatPages = [];

    /**
     * @param  array<string, mixed>  $config  Full docs-builder configuration array.
     * @param  string  $baseUrl  The base URL path (already trimmed of trailing slash).
     */
    public function __construct(array $config, string $baseUrl)
    {
        $this->config = $config;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Build the navigation structure with slugs and URLs.
     *
     * @return array<int, NavigationSection>
     */
    public function build(): array
    {
        $navigation = [];

        foreach ($this->config['navigation'] ?? [] as $section) {
            $builtSection = [
                'title' => $section['title'],
                'pages' => [],
            ];

            foreach ($section['pages'] ?? [] as $page) {
                $slug = $this->fileToSlug($page['file']);
                $url = $this->slugToUrl($slug);
                $layout = $page['layout'] ?? DocsBuilder::LAYOUT_DOCUMENTATION;

                $builtSection['pages'][] = new Page(
                    title: $page['title'],
                    file: $page['file'],
                    slug: $slug,
                    url: $url,
                    icon: $page['icon'] ?? null,
                    layout: $layout,
                );
            }

            $navigation[] = $builtSection;
        }

        return $navigation;
    }

    /**
     * Build a flat list of pages for prev/next navigation.
     *
     * @param  array<int, NavigationSection>  $navigation
     * @return array<int, Page>
     */
    public function buildFlatPageList(array $navigation): array
    {
        $pages = [];
        foreach ($navigation as $section) {
            foreach ($section['pages'] ?? [] as $page) {
                $pages[] = $page;
            }
        }

        $this->flatPages = $pages;

        return $pages;
    }

    /**
     * Get previous and next pages for navigation.
     *
     * @return array{0: PrevNextPage|null, 1: PrevNextPage|null}
     */
    public function getPrevNextPages(string $currentSlug): array
    {
        $currentIndex = null;
        foreach ($this->flatPages as $i => $page) {
            if ($page['slug'] === $currentSlug) {
                $currentIndex = $i;
                break;
            }
        }

        if ($currentIndex === null) {
            return [null, null];
        }

        $prev = $currentIndex > 0 ? [
            'title' => $this->flatPages[$currentIndex - 1]['title'],
            'url' => $this->flatPages[$currentIndex - 1]['url'],
        ] : null;

        $next = $currentIndex < count($this->flatPages) - 1 ? [
            'title' => $this->flatPages[$currentIndex + 1]['title'],
            'url' => $this->flatPages[$currentIndex + 1]['url'],
        ] : null;

        return [$prev, $next];
    }

    /**
     * Convert a filename to a URL slug.
     */
    public function fileToSlug(string $file): string
    {
        // Remove .md extension
        $slug = preg_replace('/\.md$/i', '', $file);

        // README becomes index
        if ($slug === 'README') {
            return 'index';
        }

        return $slug;
    }

    /**
     * Convert a slug to a full URL.
     */
    public function slugToUrl(string $slug): string
    {
        if ($slug === 'index') {
            return $this->baseUrl.'/index.html';
        }

        return $this->baseUrl.'/'.$slug.'/index.html';
    }
}
