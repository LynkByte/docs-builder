<?php

namespace LynkByte\DocsBuilder;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use Tempest\Highlight\Highlighter;

class MarkdownParser
{
    private MarkdownConverter $converter;

    private Highlighter $highlighter;

    /** @var array<string, string> */
    private array $languageExtensions = [
        'php' => 'php',
        'blade' => 'blade',
        'html' => 'html',
        'css' => 'css',
        'javascript' => 'javascript',
        'js' => 'javascript',
        'json' => 'json',
        'bash' => 'bash',
        'shell' => 'bash',
        'sh' => 'bash',
        'sql' => 'sql',
        'yaml' => 'yaml',
        'yml' => 'yaml',
        'xml' => 'xml',
        'typescript' => 'typescript',
        'ts' => 'typescript',
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

        // Extract front matter if present (---\n...\n---)
        $markdown = $this->stripFrontMatter($markdown);

        // Convert markdown to HTML
        $rendered = $this->converter->convert($markdown);
        $html = $rendered->getContent();

        // Apply syntax highlighting and styled code block wrappers
        $html = $this->postProcessCodeBlocks($html);

        // Wrap tables in a scrollable container to prevent overflow
        $html = $this->postProcessTables($html);

        // Convert video URLs to responsive embeds
        $html = $this->postProcessVideos($html);

        // Wrap standalone images in <figure> and add lazy loading
        $html = $this->postProcessImages($html);

        // Extract headings from the rendered HTML
        $headings = $this->extractHeadings($html);

        // Extract first paragraph as description
        $description = $this->extractDescription($html);

        // Generate plain text for search indexing
        $plainText = $this->htmlToPlainText($html);

        return [
            'html' => $html,
            'headings' => $headings,
            'description' => $description,
            'plainText' => $plainText,
        ];
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

        // Apply syntax highlighting and styled code block wrappers
        $html = $this->postProcessCodeBlocks($html);

        // Wrap tables in a scrollable container to prevent overflow
        $html = $this->postProcessTables($html);

        // Convert video URLs to responsive embeds
        $html = $this->postProcessVideos($html);

        // Wrap standalone images in <figure> and add lazy loading
        $html = $this->postProcessImages($html);

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
     * Post-process code blocks: apply syntax highlighting and wrap in styled container.
     */
    private function postProcessCodeBlocks(string $html): string
    {
        // Convert mermaid code blocks into client-side rendered diagrams
        $html = preg_replace_callback(
            '/<pre><code class="language-mermaid">(.*?)<\/code><\/pre>/s',
            function (array $matches): string {
                $rawCode = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return '<div class="docs-mermaid-block">'
                    .'<div class="docs-mermaid-toolbar">'
                    .'<button data-mermaid-zoom-in title="Zoom in"><span class="material-symbols-outlined">zoom_in</span></button>'
                    .'<button data-mermaid-zoom-out title="Zoom out"><span class="material-symbols-outlined">zoom_out</span></button>'
                    .'<button data-mermaid-reset title="Reset zoom"><span class="material-symbols-outlined">fit_screen</span></button>'
                    .'<button data-mermaid-fullscreen title="Fullscreen"><span class="material-symbols-outlined">fullscreen</span></button>'
                    .'</div>'
                    .'<div class="docs-mermaid-content">'
                    .'<pre class="mermaid">'.trim($rawCode).'</pre>'
                    .'</div>'
                    .'</div>';
            },
            $html
        );

        // Match <pre><code class="language-X">...</code></pre> blocks (with language)
        $html = preg_replace_callback(
            '/<pre><code class="language-([^"]+)">(.*?)<\/code><\/pre>/s',
            function (array $matches): string {
                $language = $matches[1];
                $code = $matches[2];

                return $this->buildStyledCodeBlock($code, $language);
            },
            $html
        );

        // Match plain <pre><code>...</code></pre> blocks (no language)
        $html = preg_replace_callback(
            '/<pre><code>(.*?)<\/code><\/pre>/s',
            function (array $matches): string {
                $code = $matches[1];

                return $this->buildStyledCodeBlock($code);
            },
            $html
        );

        return $html;
    }

    /**
     * Wrap tables in a scrollable container to prevent wide tables from overflowing.
     */
    private function postProcessTables(string $html): string
    {
        return preg_replace(
            '/<table([\s\S]*?)<\/table>/',
            '<div class="docs-table-wrapper"><table$1</table></div>',
            $html
        );
    }

    /**
     * Post-process images: wrap standalone images in <figure> and add lazy loading.
     */
    private function postProcessImages(string $html): string
    {
        // Wrap standalone images (sole child of a <p>) in <figure> with optional <figcaption>
        $html = preg_replace_callback(
            '/<p>\s*(<img\s[^>]*\/?>)\s*<\/p>/',
            function (array $matches): string {
                $imgTag = $matches[1];

                // Add loading="lazy" if not already present
                if (! str_contains($imgTag, 'loading=')) {
                    $imgTag = str_replace('<img ', '<img loading="lazy" ', $imgTag);
                }

                // Extract alt text for figcaption
                $alt = '';
                if (preg_match('/alt="([^"]*)"/', $imgTag, $altMatch)) {
                    $alt = $altMatch[1];
                }

                $figure = '<figure class="docs-figure">'.$imgTag;
                if ($alt !== '') {
                    $figure .= '<figcaption>'.$alt.'</figcaption>';
                }
                $figure .= '</figure>';

                return $figure;
            },
            $html
        );

        // Add loading="lazy" to any remaining inline images not already processed
        $html = preg_replace(
            '/<img(?![^>]*loading=)([^>]*?)(\s*\/?>)/',
            '<img loading="lazy"$1$2',
            $html
        );

        return $html;
    }

    /**
     * Post-process video URLs: convert autolinked video URLs to responsive embeds.
     */
    private function postProcessVideos(string $html): string
    {
        // Match paragraphs containing only a single autolinked URL (href and text both start with http)
        return preg_replace_callback(
            '/<p>\s*<a href="(https?:\/\/[^"]+)">https?:\/\/[^<]+<\/a>\s*<\/p>/',
            function (array $matches): string {
                $url = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                // YouTube: youtube.com/watch?v=ID, youtu.be/ID, youtube.com/embed/ID
                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]+)/', $url, $m)) {
                    return $this->buildYouTubeEmbed($m[1]);
                }

                // Vimeo: vimeo.com/ID, player.vimeo.com/video/ID
                if (preg_match('/(?:vimeo\.com\/|player\.vimeo\.com\/video\/)(\d+)/', $url, $m)) {
                    return $this->buildVimeoEmbed($m[1]);
                }

                // Local video files: .mp4, .webm, .ogg
                if (preg_match('/\.(mp4|webm|ogg)(?:\?[^"]*)?$/i', $url)) {
                    return $this->buildVideoElement($url);
                }

                // Not a recognized video URL, return unchanged
                return $matches[0];
            },
            $html
        );
    }

    /**
     * Build a responsive YouTube embed iframe.
     */
    private function buildYouTubeEmbed(string $videoId): string
    {
        return '<div class="docs-video-wrapper">'
            .'<iframe src="https://www.youtube-nocookie.com/embed/'.$videoId.'"'
            .' frameborder="0"'
            .' allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"'
            .' allowfullscreen></iframe>'
            .'</div>';
    }

    /**
     * Build a responsive Vimeo embed iframe.
     */
    private function buildVimeoEmbed(string $videoId): string
    {
        return '<div class="docs-video-wrapper">'
            .'<iframe src="https://player.vimeo.com/video/'.$videoId.'"'
            .' frameborder="0"'
            .' allow="autoplay; fullscreen; picture-in-picture"'
            .' allowfullscreen></iframe>'
            .'</div>';
    }

    /**
     * Build a responsive HTML5 video element.
     */
    private function buildVideoElement(string $url): string
    {
        $escapedUrl = htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return '<div class="docs-video-wrapper">'
            .'<video src="'.$escapedUrl.'" controls preload="metadata"></video>'
            .'</div>';
    }

    /**
     * Build a styled code block with syntax highlighting and the wrapper matching the reference theme.
     */
    private function buildStyledCodeBlock(string $code, ?string $language = null): string
    {
        // Decode HTML entities back to raw code for the highlighter
        $rawCode = html_entity_decode($code, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $rawCode = trim($rawCode);

        // Apply syntax highlighting if we have a supported language
        if ($language !== null) {
            $highlightLang = $this->resolveHighlightLanguage($language);

            try {
                $highlighted = $this->highlighter->parse($rawCode, $highlightLang);
            } catch (\Throwable) {
                // Fallback: re-encode and use plain text
                $highlighted = htmlspecialchars($rawCode, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        } else {
            $highlighted = htmlspecialchars($rawCode, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Determine the display filename for the header
        $displayName = $this->getCodeBlockLabel($language);

        // Build the wrapper HTML matching the reference theme structure
        $header = '<div class="docs-code-header">'
            .'<span>'.htmlspecialchars($displayName).'</span>'
            .'<button class="docs-copy-btn" data-copy-code>'
            .'<span class="material-symbols-outlined" style="font-size:14px;">content_copy</span>'
            .'<span>Copy</span>'
            .'</button>'
            .'</div>';

        return '<div class="docs-code-block">'
            .$header
            .'<div class="docs-code-body">'
            .'<pre class="hl"><code>'.$highlighted.'</code></pre>'
            .'</div>'
            .'</div>';
    }

    /**
     * Resolve the highlight language string for tempest/highlight.
     */
    private function resolveHighlightLanguage(string $language): string
    {
        $language = strtolower(trim($language));

        return $this->languageExtensions[$language] ?? $language;
    }

    /**
     * Get a display label for the code block header.
     */
    private function getCodeBlockLabel(?string $language): string
    {
        if ($language === null) {
            return 'Code';
        }

        $labels = [
            'php' => 'PHP',
            'blade' => 'Blade',
            'html' => 'HTML',
            'css' => 'CSS',
            'javascript' => 'JavaScript',
            'js' => 'JavaScript',
            'json' => 'JSON',
            'bash' => 'Terminal',
            'shell' => 'Terminal',
            'sh' => 'Terminal',
            'sql' => 'SQL',
            'yaml' => 'YAML',
            'yml' => 'YAML',
            'xml' => 'XML',
            'typescript' => 'TypeScript',
            'ts' => 'TypeScript',
        ];

        return $labels[strtolower($language)] ?? strtoupper($language);
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
        $text = preg_replace('/<pre[^>]*>.*?<\/pre>/s', '', $html);

        // Strip HTML tags
        $text = strip_tags($text);

        // Decode entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim and limit to ~1000 chars for search index
        $text = trim($text);
        if (mb_strlen($text) > 1000) {
            $text = mb_substr($text, 0, 1000);
        }

        return $text;
    }
}
