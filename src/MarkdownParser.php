<?php

namespace LynkByte\DocsBuilder;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use LynkByte\DocsBuilder\Contracts\MarkdownParserInterface;
use Tempest\Highlight\Highlighter;

class MarkdownParser implements MarkdownParserInterface
{
    private MarkdownConverter $converter;

    private Highlighter $highlighter;

    private MarkdownPostProcessor $postProcessor;

    /** @var array<string, array{highlight: string, label: string}> */
    private array $languageMap = [
        'php' => ['highlight' => 'php', 'label' => 'PHP'],
        'blade' => ['highlight' => 'blade', 'label' => 'Blade'],
        'html' => ['highlight' => 'html', 'label' => 'HTML'],
        'css' => ['highlight' => 'css', 'label' => 'CSS'],
        'javascript' => ['highlight' => 'javascript', 'label' => 'JavaScript'],
        'js' => ['highlight' => 'javascript', 'label' => 'JavaScript'],
        'json' => ['highlight' => 'json', 'label' => 'JSON'],
        'bash' => ['highlight' => 'bash', 'label' => 'Terminal'],
        'shell' => ['highlight' => 'bash', 'label' => 'Terminal'],
        'sh' => ['highlight' => 'bash', 'label' => 'Terminal'],
        'sql' => ['highlight' => 'sql', 'label' => 'SQL'],
        'yaml' => ['highlight' => 'yaml', 'label' => 'YAML'],
        'yml' => ['highlight' => 'yaml', 'label' => 'YAML'],
        'xml' => ['highlight' => 'xml', 'label' => 'XML'],
        'typescript' => ['highlight' => 'typescript', 'label' => 'TypeScript'],
        'ts' => ['highlight' => 'typescript', 'label' => 'TypeScript'],
    ];

    public function __construct()
    {
        $environment = new Environment([
            'heading_permalink' => [
                'html_class' => 'header-anchor',
                'id_prefix' => '',
                'apply_id_to_heading' => true,
                'heading_class' => '',
                'fragment_prefix' => '',
                'insert' => 'after',
                'min_heading_level' => 1,
                'max_heading_level' => 4,
                'title' => 'Permalink',
                'symbol' => '#',
                'aria_hidden' => true,
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new HeadingPermalinkExtension);

        $this->converter = new MarkdownConverter($environment);
        $this->highlighter = new Highlighter;
        $this->postProcessor = new MarkdownPostProcessor;
    }

    /**
     * Parse a markdown file and return structured data.
     *
     * @return array{html: string, headings: array<int, array{id: string, text: string, level: int}>, description: string, plainText: string}
     */
    public function parse(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return [
                'html' => '',
                'headings' => [],
                'description' => '',
                'plainText' => '',
            ];
        }

        $markdown = file_get_contents($filePath);

        if ($markdown === false) {
            throw new \RuntimeException("Unable to read markdown file [{$filePath}].");
        }

        // Extract front matter if present (---\n...\n---)
        $markdown = $this->stripFrontMatter($markdown);

        // Convert markdown to HTML
        $rendered = $this->converter->convert($markdown);
        $html = $rendered->getContent();

        return $this->processHtml($html);
    }

    /**
     * Parse raw markdown string (not from a file).
     *
     * @return array{html: string, headings: array<int, array{id: string, text: string, level: int}>, description: string, plainText: string}
     */
    public function parseString(string $markdown): array
    {
        $markdown = $this->stripFrontMatter($markdown);

        $rendered = $this->converter->convert($markdown);
        $html = $rendered->getContent();

        return $this->processHtml($html);
    }

    /**
     * Apply the shared post-processing pipeline to rendered HTML.
     *
     * @return array{html: string, headings: array<int, array{id: string, text: string, level: int}>, description: string, plainText: string}
     */
    private function processHtml(string $html): array
    {
        $html = $this->postProcessor->processCodeBlocks($html, $this->highlighter, $this->languageMap);
        $html = $this->postProcessor->processTables($html);
        $html = $this->postProcessor->processVideos($html);
        $html = $this->postProcessor->processImages($html);

        return [
            'html' => $html,
            'headings' => $this->extractHeadings($html),
            'description' => $this->extractDescription($html),
            'plainText' => $this->htmlToPlainText($html),
        ];
    }

    /**
     * Strip YAML front matter from markdown content.
     */
    private function stripFrontMatter(string $markdown): string
    {
        if (str_starts_with($markdown, '---')) {
            $endPos = strpos($markdown, '---', 3);
            if ($endPos !== false) {
                return ltrim(substr($markdown, $endPos + 3));
            }
        }

        return $markdown;
    }

    /**
     * Extract headings (h2, h3, h4) from rendered HTML for the table of contents.
     *
     * @return array<int, array{id: string, text: string, level: int}>
     */
    private function extractHeadings(string $html): array
    {
        $headings = [];

        // Match h2, h3, h4 tags with their id attributes
        if (preg_match_all('/<h([2-4])\s+id="([^"]*)"[^>]*>(.*?)<\/h\1>/s', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $level = (int) $match[1];
                $id = $match[2];
                // Strip HTML tags from heading text (remove permalink anchors etc.)
                $text = trim(strip_tags($match[3]));
                // Remove trailing # from permalink
                $text = rtrim($text, ' #');

                $headings[] = [
                    'id' => $id,
                    'text' => $text,
                    'level' => $level,
                ];
            }
        }

        return $headings;
    }

    /**
     * Extract the first paragraph text as a description.
     */
    private function extractDescription(string $html): string
    {
        // Match first <p> tag content, but skip blockquotes
        if (preg_match('/<p>(.*?)<\/p>/s', $html, $match)) {
            $text = strip_tags($match[1]);
            $text = trim($text);

            // Limit to ~200 chars
            if (mb_strlen($text) > 200) {
                $text = mb_substr($text, 0, 197).'...';
            }

            return $text;
        }

        return '';
    }

    /**
     * Convert HTML to plain text for search indexing.
     */
    private function htmlToPlainText(string $html): string
    {
        // Remove code blocks entirely (not useful for search summaries)
        $text = preg_replace('/<pre[^>]*>.*?<\/pre>/s', '', $html) ?? '';

        // Strip HTML tags
        $text = strip_tags($text);

        // Decode entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        // Trim and limit to ~1000 chars for search index
        $text = trim($text);
        if (mb_strlen($text) > 1000) {
            $text = mb_substr($text, 0, 1000);
        }

        return $text;
    }
}
