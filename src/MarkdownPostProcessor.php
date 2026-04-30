<?php

namespace LynkByte\DocsBuilder;

class MarkdownPostProcessor
{
    /**
     * Post-process code blocks: apply syntax highlighting and wrap in styled container.
     *
     * @param  array<string, array{highlight: string, label: string}>  $languageMap
     */
    public function processCodeBlocks(string $html, \Tempest\Highlight\Highlighter $highlighter, array $languageMap): string
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
                    .'<button data-mermaid-pan title="Toggle pan"><span class="material-symbols-outlined">drag_pan</span></button>'
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
            function (array $matches) use ($highlighter, $languageMap): string {
                $language = $matches[1];
                $code = $matches[2];

                return $this->buildStyledCodeBlock($code, $highlighter, $languageMap, $language);
            },
            $html
        );

        // Match plain <pre><code>...</code></pre> blocks (no language)
        $html = preg_replace_callback(
            '/<pre><code>(.*?)<\/code><\/pre>/s',
            function (array $matches) use ($highlighter, $languageMap): string {
                $code = $matches[1];

                return $this->buildStyledCodeBlock($code, $highlighter, $languageMap);
            },
            $html
        );

        return $html;
    }

    /**
     * Wrap tables in a scrollable container to prevent wide tables from overflowing.
     */
    public function processTables(string $html): string
    {
        return preg_replace(
            '/<table([\s\S]*?)<\/table>/',
            '<div class="docs-table-wrapper"><table$1</table></div>',
            $html
        );
    }

    /**
     * Post-process video URLs: convert autolinked video URLs to responsive embeds.
     */
    public function processVideos(string $html): string
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
        ) ?? $html;
    }

    /**
     * Post-process images: wrap standalone images in <figure> and add lazy loading.
     */
    public function processImages(string $html): string
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
        ) ?? $html;

        // Add loading="lazy" to any remaining inline images not already processed
        $html = preg_replace(
            '/<img(?![^>]*loading=)([^>]*?)(\s*\/?>)/',
            '<img loading="lazy"$1$2',
            $html
        ) ?? $html;

        return $html;
    }

    /**
     * Build a responsive YouTube embed iframe.
     */
    private function buildYouTubeEmbed(string $videoId): string
    {
        return '<div class="docs-video-wrapper">'
            .'<iframe src="https://www.youtube-nocookie.com/embed/'.$videoId.'"'
            .' title="YouTube video player"'
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
            .' title="Vimeo video player"'
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
     *
     * @param  array<string, array{highlight: string, label: string}>  $languageMap
     */
    private function buildStyledCodeBlock(string $code, \Tempest\Highlight\Highlighter $highlighter, array $languageMap, ?string $language = null): string
    {
        // Decode HTML entities back to raw code for the highlighter
        $rawCode = html_entity_decode($code, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $rawCode = trim($rawCode);

        // Apply syntax highlighting if we have a supported language
        if ($language !== null) {
            $highlightLang = $this->resolveHighlightLanguage($language, $languageMap);

            try {
                $highlighted = $highlighter->parse($rawCode, $highlightLang);
            } catch (\Throwable) {
                // Fallback: re-encode and use plain text
                $highlighted = htmlspecialchars($rawCode, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }
        } else {
            $highlighted = htmlspecialchars($rawCode, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Determine the display filename for the header
        $displayName = $this->getCodeBlockLabel($language, $languageMap);

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
     *
     * @param  array<string, array{highlight: string, label: string}>  $languageMap
     */
    private function resolveHighlightLanguage(string $language, array $languageMap): string
    {
        $language = strtolower(trim($language));

        return $languageMap[$language]['highlight'] ?? $language;
    }

    /**
     * Get a display label for the code block header.
     *
     * @param  array<string, array{highlight: string, label: string}>  $languageMap
     */
    private function getCodeBlockLabel(?string $language, array $languageMap): string
    {
        if ($language === null) {
            return 'Code';
        }

        return $languageMap[strtolower($language)]['label'] ?? strtoupper($language);
    }
}
