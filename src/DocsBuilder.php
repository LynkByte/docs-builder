<?php

namespace LynkByte\DocsBuilder;

use Illuminate\Support\Facades\File;
use LynkByte\DocsBuilder\Contracts\MarkdownParserInterface;
use LynkByte\DocsBuilder\Contracts\OpenApiParserInterface;
use LynkByte\DocsBuilder\Data\Endpoint;
use LynkByte\DocsBuilder\Data\Page;

/**
 * @phpstan-type NavigationSection array{title: string, pages: array<int, Page>}
 * @phpstan-type ParsedMarkdown array{html: string, description: string, plainText: string, headings: array<int, array{text: string, level: int, id: string}>}
 * @phpstan-type BreadcrumbItem array{title: string, url: string}
 * @phpstan-type PrevNextPage array{title: string, url: string}
 */
class DocsBuilder
{
    public const LAYOUT_DOCUMENTATION = 'documentation';

    public const LAYOUT_API_REFERENCE = 'api-reference';

    private MarkdownParserInterface $markdownParser;

    private OpenApiParserInterface $openApiParser;

    private SearchIndexBuilder $searchIndex;

    private NavigationBuilder $navigationBuilder;

    private string $sourceDir;

    private string $outputDir;

    private string $baseUrl;

    /** @var array<string, mixed> */
    private array $config;

    private int $pagesBuilt = 0;

    /** @var array<string, mixed>|null */
    private ?array $apiDataCache = null;

    /** @var array<string, array<int, Endpoint>>|null */
    private ?array $apiEndpointsCache = null;

    /**
     * @param  OpenApiParserInterface|null  $parser  Custom OpenAPI parser instance.
     * @param  array<string, mixed>|null  $config  Configuration array; falls back to config('docs-builder').
     * @param  MarkdownParserInterface|null  $markdownParser  Custom Markdown parser instance.
     * @param  SearchIndexBuilder|null  $searchIndex  Custom search index builder instance.
     *
     * @throws \InvalidArgumentException If source_dir or output_dir is missing from config.
     */
    public function __construct(?OpenApiParserInterface $parser = null, ?array $config = null, ?MarkdownParserInterface $markdownParser = null, ?SearchIndexBuilder $searchIndex = null)
    {
        $this->config = $config ?? config('docs-builder') ?? [];
        $this->markdownParser = $markdownParser ?? new MarkdownParser;
        $this->openApiParser = $parser ?? new OpenApiParser($this->config['api_tag_icons'] ?? []);
        $this->searchIndex = $searchIndex ?? new SearchIndexBuilder;

        if (empty($this->config['source_dir'])) {
            throw new \InvalidArgumentException('The docs-builder "source_dir" configuration value is required.');
        }

        if (empty($this->config['output_dir'])) {
            throw new \InvalidArgumentException('The docs-builder "output_dir" configuration value is required.');
        }

        $this->sourceDir = $this->config['source_dir'];
        $this->outputDir = $this->config['output_dir'];
        $this->baseUrl = rtrim($this->config['base_url'] ?? '/docs', '/');

        $this->navigationBuilder = new NavigationBuilder($this->config, $this->baseUrl);
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
        $navigation = $this->navigationBuilder->build();

        // Build flat page list for prev/next navigation
        $this->navigationBuilder->buildFlatPageList($navigation);

        // Build documentation pages
        foreach ($navigation as $section) {
            foreach ($section['pages'] as $page) {
                $this->buildPage($page, $section['title'], $navigation);
            }
        }

        // Build API reference pages from OpenAPI spec
        $this->buildApiReference($navigation);

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
     * Build the base view data array common to all page types.
     *
     * @param  Page  $page  The page being rendered.
     * @param  string  $pageDescription  Description text for the page.
     * @param  string  $content  Rendered HTML content.
     * @param  array<int, NavigationSection>  $navigation  Full navigation structure.
     * @param  array<int, BreadcrumbItem>  $breadcrumbs  Breadcrumb trail.
     * @return array<string, mixed>
     */
    private function buildBaseViewData(Page $page, string $pageDescription, string $content, array $navigation, array $breadcrumbs): array
    {
        return array_merge($this->resolveSharedViewData(), [
            'baseUrl' => $this->baseUrl,
            'siteName' => $this->config['site_name'] ?? '',
            'siteDescription' => $this->config['site_description'] ?? '',
            'pageTitle' => $page['title'],
            'pageDescription' => $pageDescription,
            'content' => $content,
            'navigation' => $navigation,
            'currentPage' => $page['slug'],
            'breadcrumbs' => $breadcrumbs,
            'footer' => $this->config['footer'] ?? null,
        ]);
    }

    /**
     * Get parsed OpenAPI data, caching the result for reuse.
     *
     * @return array<string, mixed>|null
     */
    private function getApiData(): ?array
    {
        if ($this->apiDataCache !== null) {
            return $this->apiDataCache;
        }

        $openApiFile = $this->config['openapi_file'] ?? null;
        if ($openApiFile === null || ! file_exists($openApiFile)) {
            return null;
        }

        $this->apiDataCache = $this->openApiParser->parse($openApiFile);

        return $this->apiDataCache;
    }

    /**
     * Get API endpoints with URLs, caching the result for reuse.
     *
     * @return array<string, array<int, Endpoint>>
     */
    private function getApiEndpoints(): array
    {
        if ($this->apiEndpointsCache !== null) {
            return $this->apiEndpointsCache;
        }

        $apiData = $this->getApiData();
        $this->apiEndpointsCache = [];

        if ($apiData) {
            foreach ($apiData['endpoints'] as $tag => $endpoints) {
                $this->apiEndpointsCache[$tag] = [];
                foreach ($endpoints as $endpoint) {
                    $endpointSlug = 'api-reference/'.$endpoint['operationId'];
                    $this->apiEndpointsCache[$tag][] = $endpoint->withUrl(
                        $this->baseUrl.'/'.$endpointSlug.'/index.html'
                    );
                }
            }
        }

        return $this->apiEndpointsCache;
    }

    /**
     * Build a single documentation page.
     *
     * @param  array<int, NavigationSection>  $navigation
     */
    private function buildPage(Page $page, string $sectionTitle, array $navigation): void
    {
        $filePath = $this->sourceDir.'/'.$page['file'];

        if (! file_exists($filePath)) {
            return;
        }

        // Parse markdown
        $parsed = $this->markdownParser->parse($filePath);

        // Replace {SiteName} placeholder with configured site name
        $parsed['html'] = str_replace('{SiteName}', $this->config['site_name'] ?? '', $parsed['html']);
        $parsed['description'] = str_replace('{SiteName}', $this->config['site_name'] ?? '', $parsed['description']);
        $parsed['plainText'] = str_replace('{SiteName}', $this->config['site_name'] ?? '', $parsed['plainText']);

        // Rewrite inter-document .md links to proper web URLs
        $parsed['html'] = $this->postProcessLinks($parsed['html']);

        // Determine prev/next pages
        [$prevPage, $nextPage] = $this->navigationBuilder->getPrevNextPages($page['slug']);

        // Build breadcrumbs
        $breadcrumbs = [
            ['title' => 'Docs', 'url' => $this->baseUrl.'/index.html'],
            ['title' => $sectionTitle, 'url' => ''],
            ['title' => $page['title'], 'url' => $page['url']],
        ];

        // Determine which layout to use
        $layout = $page['layout'] ?? self::LAYOUT_DOCUMENTATION;

        if ($layout === self::LAYOUT_API_REFERENCE) {
            // For the API reference markdown page, use api-reference layout
            // but without endpoint-specific data (it's the overview page)
            $this->buildApiReferencePage($page, $parsed, $navigation, $breadcrumbs);
        } else {
            // Standard documentation page
            $viewData = array_merge(
                $this->buildBaseViewData($page, $parsed['description'], $parsed['html'], $navigation, $breadcrumbs),
                [
                    'tableOfContents' => $parsed['headings'],
                    'prevPage' => $prevPage,
                    'nextPage' => $nextPage,
                ]
            );

            $html = view('docs-builder::docs.layouts.'.self::LAYOUT_DOCUMENTATION, $viewData)->render();
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
     * @param  ParsedMarkdown  $parsed
     * @param  array<int, NavigationSection>  $navigation
     * @param  array<int, BreadcrumbItem>  $breadcrumbs
     */
    private function buildApiReferencePage(Page $page, array $parsed, array $navigation, array $breadcrumbs): void
    {
        $apiData = $this->getApiData();
        $apiEndpoints = $this->getApiEndpoints();

        $viewData = $this->buildApiReferenceViewData(
            $page, $parsed['description'], $navigation, $breadcrumbs, $apiEndpoints, $apiData ?? []
        );

        // Override content with the parsed markdown HTML for the overview page
        $viewData['content'] = $parsed['html'];

        $html = view('docs-builder::docs.layouts.'.self::LAYOUT_API_REFERENCE, $viewData)->render();
        $this->writePage($page['slug'], $html);
    }

    /**
     * Build individual API endpoint pages from the OpenAPI spec.
     *
     * @param  array<int, NavigationSection>  $navigation
     */
    private function buildApiReference(array $navigation): void
    {
        $apiData = $this->getApiData();
        if (! $apiData) {
            return;
        }

        $apiEndpoints = $this->getApiEndpoints();

        // Build individual endpoint pages
        foreach ($apiData['endpoints'] as $tag => $endpoints) {
            foreach ($endpoints as $endpoint) {
                $endpointSlug = 'api-reference/'.$endpoint['operationId'];

                $breadcrumbs = [
                    ['title' => 'Docs', 'url' => $this->baseUrl.'/index.html'],
                    ['title' => 'API Reference', 'url' => $this->baseUrl.'/api-reference/index.html'],
                    ['title' => $endpoint['summary'], 'url' => ''],
                ];

                $endpointPage = new Page(
                    title: $endpoint['summary'],
                    file: '',
                    slug: 'api-reference',
                    url: $this->baseUrl.'/'.$endpointSlug.'/index.html',
                );

                $viewData = $this->buildApiReferenceViewData(
                    $endpointPage, $endpoint['description'], $navigation, $breadcrumbs, $apiEndpoints, $apiData, $endpoint
                );

                $html = view('docs-builder::docs.layouts.'.self::LAYOUT_API_REFERENCE, $viewData)->render();
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
    }

    /**
     * Assemble the view data array for an API reference page.
     *
     * @param  Page  $page  The page being rendered.
     * @param  string  $description  Page description text.
     * @param  array<int, NavigationSection>  $navigation  Full navigation structure.
     * @param  array<int, BreadcrumbItem>  $breadcrumbs  Breadcrumb trail.
     * @param  array<string, array<int, Endpoint>>  $apiEndpoints  All endpoints grouped by tag.
     * @param  array<string, mixed>  $apiData  Parsed OpenAPI data.
     * @param  Endpoint|null  $endpoint  The specific endpoint being rendered, or null for the overview page.
     * @return array<string, mixed>
     */
    private function buildApiReferenceViewData(
        Page $page,
        string $description,
        array $navigation,
        array $breadcrumbs,
        array $apiEndpoints,
        array $apiData,
        ?Endpoint $endpoint = null,
    ): array {
        return array_merge(
            $this->buildBaseViewData($page, $description, '', $navigation, $breadcrumbs),
            [
                'apiEndpoints' => $apiEndpoints,
                'tagIcons' => $apiData['tagIcons'] ?? [],
                'apiVersion' => $apiData['info']['version'] ?? 'v1',
                'apiServerUrl' => $apiData['serverUrl'] ?? '',
                'endpointMethod' => $endpoint?->method,
                'endpointPath' => $endpoint?->path,
                'parameters' => $endpoint?->parameters ?? [],
                'pathParameters' => $endpoint?->pathParameters ?? [],
                'queryParameters' => $endpoint?->queryParameters ?? [],
                'bodyParameters' => $endpoint?->bodyParameters ?? [],
                'responses' => $endpoint?->responses ?? [],
                'currentEndpoint' => $endpoint?->operationId,
            ]
        );
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
     * Rewrite inter-document .md links in rendered HTML to proper web URLs.
     *
     * Converts relative hrefs like "installation.md" or "README.md#section"
     * into the correct output paths (e.g. "/docs/installation/index.html#section").
     * External URLs and non-.md links are left unchanged.
     */
    private function postProcessLinks(string $html): string
    {
        return preg_replace_callback(
            '/<a\s([^>]*?)href="([^"]*\.md)(#[^"]*)?"/i',
            function (array $matches): string {
                $before = $matches[1];
                $href = $matches[2];
                $fragment = $matches[3] ?? '';

                // Skip external / absolute URLs
                if (preg_match('#^(https?://|//|mailto:|tel:)#i', $href)) {
                    return $matches[0];
                }

                // Convert the .md filename to a slug, then to a full URL
                $slug = $this->navigationBuilder->fileToSlug($href);
                $url = $this->navigationBuilder->slugToUrl($slug);

                return '<a '.$before.'href="'.$url.$fragment.'"';
            },
            $html
        ) ?? $html;
    }
}
