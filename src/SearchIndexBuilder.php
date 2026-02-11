<?php

namespace LynkByte\DocsBuilder;

class SearchIndexBuilder
{
    /** @var array<int, array<string, mixed>> */
    private array $entries = [];

    /**
     * Add a documentation page entry to the search index.
     *
     * @param  array<int, array{id: string, text: string, level: int}>  $headings
     */
    public function addPage(
        string $title,
        string $url,
        string $section,
        array $headings,
        string $plainText,
        string $description,
        string $icon = 'description',
    ): void {
        $this->entries[] = [
            'title' => $title,
            'url' => $url,
            'section' => $section,
            'headings' => implode(', ', array_map(fn (array $h): string => $h['text'], $headings)),
            'content' => $plainText,
            'description' => $description,
            'type' => 'doc',
            'icon' => $icon,
        ];
    }

    /**
     * Add an API endpoint entry to the search index.
     */
    public function addEndpoint(
        string $title,
        string $url,
        string $method,
        string $path,
        string $description,
        string $tag,
    ): void {
        $this->entries[] = [
            'title' => $title,
            'url' => $url,
            'section' => 'API Reference',
            'headings' => "$method $path",
            'content' => $description,
            'description' => "$method $path",
            'type' => 'api-endpoint',
            'icon' => 'api',
            'method' => $method,
        ];
    }

    /**
     * Build the search index JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Write the search index to a file.
     */
    public function writeTo(string $filePath): void
    {
        $dir = dirname($filePath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($filePath, $this->toJson());
    }

    /**
     * Get the number of entries in the index.
     */
    public function count(): int
    {
        return count($this->entries);
    }
}
