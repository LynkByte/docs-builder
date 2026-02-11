<?php

namespace LynkByte\DocsBuilder;

use Illuminate\Support\Facades\File;

class DocsBuilder
{
    private MarkdownParser $markdownParser;

    private OpenApiParser $openApiParser;

    private SearchIndexBuilder $searchIndex;

    private string $sourceDir;

    private string $outputDir;

    private string $baseUrl;

    /** @var array<string, mixed> */
    private array $config;

    /** @var array<int, array<string, mixed>> */
    private array $flatPages = [];

    private int $pagesBuilt = 0;

    public function __construct()
    {
        $this->markdownParser = new MarkdownParser;
        $this->openApiParser = new OpenApiParser;
        $this->searchIndex = new SearchIndexBuilder;
        $this->config = config('docs-builder');
        $this->sourceDir = $this->config['source_dir'];
        $this->outputDir = $this->config['output_dir'];
        $this->baseUrl = rtrim($this->config['base_url'], '/');
    }

    /**
     * Build all documentation pages.
     *
     * @return array{pages: int, searchEntries: int}
     */
    public function build(): array
    {
        // Clean output directory
        $this->cleanOutput();

        // Build navigation with slugs and URLs
        $navigation = $this->buildNavigation();

        // Build flat page list for prev/next navigation
        $this->flatPages = $this->buildFlatPageList($navigation);

        // Build documentation pages
        foreach ($navigation as $section) {
            foreach ($section['pages'] as $page) {
                $this->buildPage($page, $section['title'], $navigation);
            }
        }

        // Build API reference pages from OpenAPI spec
        $apiData = $this->buildApiReference($navigation);

        // Write search index
        $this->searchIndex->writeTo($this->outputDir.'/search-index.json');

        return [
            'pages' => $this->pagesBuilt,
            'searchEntries' => $this->searchIndex->count(),
        ];
    }

    /**
     * Clean the output directory, preserving the assets folder.
     */
    private function cleanOutput(): void
    {
        if (is_dir($this->outputDir)) {
            // Remove everything except assets directory
            $items = File::glob($this->outputDir.'/*');
            foreach ($items as $item) {
                if (basename($item) === 'assets') {
                    continue;
                }
                if (is_dir($item)) {
                    File::deleteDirectory($item);
                } else {
                    File::delete($item);
                }
            }
        } else {
            File::makeDirectory($this->outputDir, 0755, true);
        }
    }

    /**
     * Build the navigation structure with slugs and URLs.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildNavigation(): array
    {
        $navigation = [];

        foreach ($this->config['navigation'] as $section) {
            $builtSection = [
                'title' => $section['title'],
                'pages' => [],
            ];

            foreach ($section['pages'] as $page) {
                $slug = $this->fileToSlug($page['file']);
                $url = $this->slugToUrl($slug);
                $layout = $page['layout'] ?? 'documentation';

                $builtSection['pages'][] = [
                    'title' => $page['title'],
                    'file' => $page['file'],
                    'slug' => $slug,
                    'url' => $url,
                    'icon' => $page['icon'] ?? null,
                    'layout' => $layout,
                ];
            }

            $navigation[] = $builtSection;
        }

        return $navigation;
    }

    /**
     * Build a flat list of pages for prev/next navigation.
     *
     * @param  array<int, array<string, mixed>>  $navigation
     * @return array<int, array<string, mixed>>
     */
    private function buildFlatPageList(array $navigation): array
    {
        $pages = [];
        foreach ($navigation as $section) {
            foreach ($section['pages'] as $page) {
                $pages[] = $page;
            }
        }

        return $pages;
    }

    /**
     * Resolve shared view data common to all page types.
     *
     * @return array<string, mixed>
     */
    private function resolveSharedViewData(): array
    {
        $logo = $this->config['logo'] ?? null;
        $headerNav = $this->config['header_nav'] ?? null;
        $fonts = $this->config['fonts'] ?? null;
        $theme = $this->config['theme'] ?? [];

        return [
            'logo' => $logo,
            'headerNav' => $headerNav,
            'fonts' => $fonts,
            'themeOverrides' => $theme,
        ];
    }

    /**
     * Build a single documentation page.
     *
     * @param  array<string, mixed>  $page
     * @param  array<int, array<string, mixed>>  $navigation
     */
    private function buildPage(array $page, string $sectionTitle, array $navigation): void
    {
        $filePath = $this->sourceDir.'/'.$page['file'];

        if (! file_exists($filePath)) {
            return;
        }

        // Parse markdown
        $parsed = $this->markdownParser->parse($filePath);

        // Replace {SiteName} placeholder with configured site name
        $parsed['html'] = str_replace('{SiteName}', $this->config['site_name'], $parsed['html']);
        $parsed['description'] = str_replace('{SiteName}', $this->config['site_name'], $parsed['description']);
        $parsed['plainText'] = str_replace('{SiteName}', $this->config['site_name'], $parsed['plainText']);

        // Determine prev/next pages
        [$prevPage, $nextPage] = $this->getPrevNextPages($page['slug']);

        // Build breadcrumbs
        $breadcrumbs = [
            ['title' => 'Docs', 'url' => $this->baseUrl.'/index.html'],
            ['title' => $sectionTitle, 'url' => ''],
            ['title' => $page['title'], 'url' => $page['url']],
        ];

        // Determine which layout to use
        $layout = $page['layout'] ?? 'documentation';

        if ($layout === 'api-reference') {
            // For the API reference markdown page, use api-reference layout
            // but without endpoint-specific data (it's the overview page)
            $this->buildApiReferencePage($page, $parsed, $navigation, $breadcrumbs);
        } else {
            // Standard documentation page
            $viewData = array_merge($this->resolveSharedViewData(), [
                'baseUrl' => $this->baseUrl,
                'siteName' => $this->config['site_name'],
                'siteDescription' => $this->config['site_description'],
                'pageTitle' => $page['title'],
                'pageDescription' => $parsed['description'],
                'content' => $parsed['html'],
                'navigation' => $navigation,
                'currentPage' => $page['slug'],
                'tableOfContents' => $parsed['headings'],
                'breadcrumbs' => $breadcrumbs,
                'prevPage' => $prevPage,
                'nextPage' => $nextPage,
                'footer' => $this->config['footer'],
            ]);

            $html = view('docs-builder::docs.layouts.documentation', $viewData)->render();
            $this->writePage($page['slug'], $html);
        }

        // Add to search index
        $this->searchIndex->addPage(
            title: $page['title'],
            url: $page['url'],
            section: $sectionTitle,
            headings: $parsed['headings'],
            plainText: $parsed['plainText'],
            description: $parsed['description'],
            icon: $page['icon'] ?? 'description',
        );

        $this->pagesBuilt++;
    }

    /**
     * Build the API reference overview page (from the markdown file) using the api-reference layout.
     *
     * @param  array<string, mixed>  $page
     * @param  array<string, mixed>  $parsed
     * @param  array<int, array<string, mixed>>  $navigation
     * @param  array<int, array<string, string>>  $breadcrumbs
     */
    private function buildApiReferencePage(array $page, array $parsed, array $navigation, array $breadcrumbs): void
    {
        // Parse OpenAPI for sidebar navigation
        $openApiFile = $this->config['openapi_file'];
        $apiData = file_exists($openApiFile) ? $this->openApiParser->parse($openApiFile) : null;

        // Build API endpoint URLs for sidebar
        $apiEndpoints = [];
        if ($apiData) {
            foreach ($apiData['endpoints'] as $tag => $endpoints) {
                $apiEndpoints[$tag] = [];
                foreach ($endpoints as $endpoint) {
                    $endpointSlug = 'api-reference/'.$endpoint['operationId'];
                    $apiEndpoints[$tag][] = array_merge($endpoint, [
                        'url' => $this->baseUrl.'/'.$endpointSlug.'/index.html',
                    ]);
                }
            }
        }

        $viewData = array_merge($this->resolveSharedViewData(), [
            'baseUrl' => $this->baseUrl,
            'siteName' => $this->config['site_name'],
            'siteDescription' => $this->config['site_description'],
            'pageTitle' => $page['title'],
            'pageDescription' => $parsed['description'],
            'content' => $parsed['html'],
            'navigation' => $navigation,
            'currentPage' => $page['slug'],
            'breadcrumbs' => $breadcrumbs,
            'footer' => $this->config['footer'],
            // API-specific data
            'apiEndpoints' => $apiEndpoints,
            'tagIcons' => $apiData['tagIcons'] ?? [],
            'apiVersion' => $apiData['info']['version'] ?? 'v1',
            // No endpoint-specific data (this is the overview)
            'endpointMethod' => null,
            'endpointPath' => null,
            'parameters' => [],
            'responses' => [],
            'currentEndpoint' => null,
        ]);

        $html = view('docs-builder::docs.layouts.api-reference', $viewData)->render();
        $this->writePage($page['slug'], $html);
    }

    /**
     * Build individual API endpoint pages from the OpenAPI spec.
     *
     * @param  array<int, array<string, mixed>>  $navigation
     * @return array<string, mixed>|null
     */
    private function buildApiReference(array $navigation): ?array
    {
        $openApiFile = $this->config['openapi_file'];
        if (! file_exists($openApiFile)) {
            return null;
        }

        $apiData = $this->openApiParser->parse($openApiFile);

        // Build API endpoint URLs for sidebar
        $apiEndpoints = [];
        foreach ($apiData['endpoints'] as $tag => $endpoints) {
            $apiEndpoints[$tag] = [];
            foreach ($endpoints as $endpoint) {
                $endpointSlug = 'api-reference/'.$endpoint['operationId'];
                $apiEndpoints[$tag][] = array_merge($endpoint, [
                    'url' => $this->baseUrl.'/'.$endpointSlug.'/index.html',
                ]);
            }
        }

        // Build individual endpoint pages
        foreach ($apiData['endpoints'] as $tag => $endpoints) {
            foreach ($endpoints as $endpoint) {
                $endpointSlug = 'api-reference/'.$endpoint['operationId'];

                $breadcrumbs = [
                    ['title' => 'Docs', 'url' => $this->baseUrl.'/index.html'],
                    ['title' => 'API Reference', 'url' => $this->baseUrl.'/api-reference/index.html'],
                    ['title' => $endpoint['summary'], 'url' => ''],
                ];

                $viewData = array_merge($this->resolveSharedViewData(), [
                    'baseUrl' => $this->baseUrl,
                    'siteName' => $this->config['site_name'],
                    'siteDescription' => $this->config['site_description'],
                    'pageTitle' => $endpoint['summary'],
                    'pageDescription' => $endpoint['description'],
                    'content' => '',
                    'navigation' => $navigation,
                    'currentPage' => 'api-reference',
                    'breadcrumbs' => $breadcrumbs,
                    'footer' => $this->config['footer'],
                    // API-specific data
                    'apiEndpoints' => $apiEndpoints,
                    'tagIcons' => $apiData['tagIcons'],
                    'apiVersion' => $apiData['info']['version'] ?? 'v1',
                    'endpointMethod' => $endpoint['method'],
                    'endpointPath' => $endpoint['path'],
                    'parameters' => $endpoint['parameters'],
                    'responses' => $endpoint['responses'],
                    'currentEndpoint' => $endpoint['operationId'],
                ]);

                $html = view('docs-builder::docs.layouts.api-reference', $viewData)->render();
                $this->writePage($endpointSlug, $html);

                // Add to search index
                $this->searchIndex->addEndpoint(
                    title: $endpoint['summary'],
                    url: $this->baseUrl.'/'.$endpointSlug.'/index.html',
                    method: $endpoint['method'],
                    path: $endpoint['path'],
                    description: $endpoint['description'],
                    tag: $tag,
                );

                $this->pagesBuilt++;
            }
        }

        return $apiData;
    }

    /**
     * Get previous and next pages for navigation.
     *
     * @return array{0: array<string, string>|null, 1: array<string, string>|null}
     */
    private function getPrevNextPages(string $currentSlug): array
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
     * Write a page to the output directory as slug/index.html.
     */
    private function writePage(string $slug, string $html): void
    {
        // "index" slug goes to output_dir/index.html
        if ($slug === 'index' || $slug === 'README') {
            $dir = $this->outputDir;
        } else {
            $dir = $this->outputDir.'/'.$slug;
        }

        if (! is_dir($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($dir.'/index.html', $html);
    }

    /**
     * Convert a filename to a URL slug.
     */
    private function fileToSlug(string $file): string
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
    private function slugToUrl(string $slug): string
    {
        if ($slug === 'index') {
            return $this->baseUrl.'/index.html';
        }

        return $this->baseUrl.'/'.$slug.'/index.html';
    }
}
