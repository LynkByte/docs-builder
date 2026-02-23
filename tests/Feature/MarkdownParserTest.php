<?php

use LynkByte\DocsBuilder\MarkdownParser;

beforeEach(function () {
    $this->parser = new MarkdownParser;
    $this->tempDir = sys_get_temp_dir().'/docs-test-'.uniqid();
    mkdir($this->tempDir, 0755, true);
});

afterEach(function () {
    if (is_dir($this->tempDir)) {
        array_map('unlink', glob($this->tempDir.'/*'));
        rmdir($this->tempDir);
    }
});

it('converts markdown to html', function () {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, "# Hello World\n\nThis is a paragraph.");

    $result = $this->parser->parse($file);

    expect($result['html'])->toContain('<h1')
        ->and($result['html'])->toContain('Hello World')
        ->and($result['html'])->toContain('<p>This is a paragraph.</p>');
});

it('extracts headings for table of contents', function () {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, "# Title\n\n## Section One\n\n### Subsection\n\n## Section Two");

    $result = $this->parser->parse($file);

    expect($result['headings'])->toHaveCount(3)
        ->and($result['headings'][0])->toMatchArray([
            'text' => 'Section One',
            'level' => 2,
        ])
        ->and($result['headings'][1])->toMatchArray([
            'text' => 'Subsection',
            'level' => 3,
        ])
        ->and($result['headings'][2])->toMatchArray([
            'text' => 'Section Two',
            'level' => 2,
        ]);
});

it('generates heading ids for anchor links', function () {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, "## Quick Start\n\n### Installation Guide");

    $result = $this->parser->parse($file);

    expect($result['headings'][0]['id'])->toBe('quick-start')
        ->and($result['headings'][1]['id'])->toBe('installation-guide');
});

it('extracts first paragraph as description', function () {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, "# Title\n\nThis is the description paragraph.\n\nThis is the second paragraph.");

    $result = $this->parser->parse($file);

    expect($result['description'])->toBe('This is the description paragraph.');
});

it('truncates long descriptions', function () {
    $file = $this->tempDir.'/test.md';
    $longText = str_repeat('A very long sentence that keeps going. ', 10);
    file_put_contents($file, "# Title\n\n$longText");

    $result = $this->parser->parse($file);

    expect(mb_strlen($result['description']))->toBeLessThanOrEqual(200);
});

it('generates plain text for search indexing', function () {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, "# Title\n\nSome **bold** text and `inline code`.\n\n```php\necho 'hello';\n```");

    $result = $this->parser->parse($file);

    expect($result['plainText'])->toContain('Some bold text and inline code')
        ->and($result['plainText'])->not->toContain('<');
});

it('strips yaml front matter', function () {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, "---\ntitle: Test\nlayout: page\n---\n\n# Actual Content\n\nBody text.");

    $result = $this->parser->parse($file);

    expect($result['html'])->toContain('Actual Content')
        ->and($result['html'])->not->toContain('title: Test');
});

it('renders gfm tables', function () {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, "| Col A | Col B |\n|-------|-------|\n| one   | two   |");

    $result = $this->parser->parse($file);

    expect($result['html'])->toContain('<table')
        ->and($result['html'])->toContain('<th')
        ->and($result['html'])->toContain('Col A');
});

it('wraps tables in a scrollable container', function () {
    $result = $this->parser->parseString("| Col A | Col B |\n|-------|-------|\n| one   | two   |");

    expect($result['html'])->toContain('<div class="docs-table-wrapper"><table')
        ->and($result['html'])->toContain('</table></div>');
});

it('renders strikethrough', function () {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, 'This is ~~deleted~~ text.');

    $result = $this->parser->parse($file);

    expect($result['html'])->toContain('<del>deleted</del>');
});

it('adds permalink anchors to headings', function () {
    $file = $this->tempDir.'/test.md';
    file_put_contents($file, '## My Section');

    $result = $this->parser->parse($file);

    expect($result['html'])->toContain('header-anchor')
        ->and($result['html'])->toContain('id="my-section"');
});

it('returns empty data for missing file', function () {
    $result = $this->parser->parse('/nonexistent/file.md');

    expect($result['html'])->toBe('')
        ->and($result['headings'])->toBe([])
        ->and($result['description'])->toBe('')
        ->and($result['plainText'])->toBe('');
});

it('can parse a string directly', function () {
    $result = $this->parser->parseString("## Hello\n\nWorld.");

    expect($result['html'])->toContain('Hello')
        ->and($result['headings'])->toHaveCount(1)
        ->and($result['description'])->toBe('World.');
});

// =========================================================================
// IMAGE SUPPORT
// =========================================================================

it('wraps standalone images in a figure element', function () {
    $result = $this->parser->parseString('![Screenshot](https://example.com/img.png)');

    expect($result['html'])->toContain('<figure class="docs-figure">')
        ->and($result['html'])->toContain('</figure>')
        ->and($result['html'])->not->toContain('<p><img');
});

it('uses alt text as figcaption', function () {
    $result = $this->parser->parseString('![A beautiful screenshot](https://example.com/img.png)');

    expect($result['html'])->toContain('<figcaption>A beautiful screenshot</figcaption>');
});

it('omits figcaption when alt text is empty', function () {
    $result = $this->parser->parseString('![](https://example.com/img.png)');

    expect($result['html'])->toContain('<figure class="docs-figure">')
        ->and($result['html'])->not->toContain('<figcaption>');
});

it('does not wrap inline images in a figure', function () {
    $result = $this->parser->parseString('Here is ![icon](icon.png) an inline image.');

    expect($result['html'])->not->toContain('<figure')
        ->and($result['html'])->toContain('<p>');
});

it('adds lazy loading to images', function () {
    $result = $this->parser->parseString('![Screenshot](https://example.com/img.png)');

    expect($result['html'])->toContain('loading="lazy"');
});

it('adds lazy loading to inline images', function () {
    $result = $this->parser->parseString('Text with ![icon](icon.png) inline.');

    expect($result['html'])->toContain('loading="lazy"');
});

// =========================================================================
// VIDEO SUPPORT
// =========================================================================

it('converts youtube url to responsive embed', function () {
    $result = $this->parser->parseString('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

    expect($result['html'])->toContain('<div class="docs-video-wrapper">')
        ->and($result['html'])->toContain('youtube-nocookie.com/embed/dQw4w9WgXcQ')
        ->and($result['html'])->toContain('allowfullscreen');
});

it('converts short youtube url to responsive embed', function () {
    $result = $this->parser->parseString('https://youtu.be/dQw4w9WgXcQ');

    expect($result['html'])->toContain('<div class="docs-video-wrapper">')
        ->and($result['html'])->toContain('youtube-nocookie.com/embed/dQw4w9WgXcQ');
});

it('converts vimeo url to responsive embed', function () {
    $result = $this->parser->parseString('https://vimeo.com/123456789');

    expect($result['html'])->toContain('<div class="docs-video-wrapper">')
        ->and($result['html'])->toContain('player.vimeo.com/video/123456789')
        ->and($result['html'])->toContain('allowfullscreen');
});

it('converts video file url to html5 video element', function () {
    $result = $this->parser->parseString('https://example.com/demo.mp4');

    expect($result['html'])->toContain('<div class="docs-video-wrapper">')
        ->and($result['html'])->toContain('<video src="https://example.com/demo.mp4"')
        ->and($result['html'])->toContain('controls')
        ->and($result['html'])->toContain('preload="metadata"');
});

it('converts webm file url to html5 video element', function () {
    $result = $this->parser->parseString('https://example.com/demo.webm');

    expect($result['html'])->toContain('<div class="docs-video-wrapper">')
        ->and($result['html'])->toContain('<video src="https://example.com/demo.webm"')
        ->and($result['html'])->toContain('controls');
});

it('converts ogg file url to html5 video element', function () {
    $result = $this->parser->parseString('https://example.com/demo.ogg');

    expect($result['html'])->toContain('<div class="docs-video-wrapper">')
        ->and($result['html'])->toContain('<video src="https://example.com/demo.ogg"')
        ->and($result['html'])->toContain('controls');
});

it('does not convert video url with surrounding text', function () {
    $result = $this->parser->parseString('Check out https://www.youtube.com/watch?v=dQw4w9WgXcQ for more.');

    expect($result['html'])->not->toContain('docs-video-wrapper')
        ->and($result['html'])->toContain('<a href=');
});

it('does not convert non-video urls to embeds', function () {
    $result = $this->parser->parseString('https://example.com/page');

    expect($result['html'])->not->toContain('docs-video-wrapper')
        ->and($result['html'])->toContain('<a href=');
});
